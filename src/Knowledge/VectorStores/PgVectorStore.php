<?php

namespace Evolvex\AgentFabric\Knowledge\VectorStores;

use Evolvex\AgentFabric\Contracts\VectorStore;
use Illuminate\Database\ConnectionInterface;

final class PgVectorStore implements VectorStore
{
    public function __construct(private readonly ConnectionInterface $db,private readonly string $table='ai_vector_records'){}
    public function upsert(string $namespace,array $records):void
    {
        foreach($records as $record)$this->db->table($this->table)->updateOrInsert(['namespace'=>$namespace,'record_id'=>(string)$record['id']],['embedding'=>json_encode($record['vector']),'metadata'=>json_encode($record['metadata']??[]),'content'=>$record['content']??null,'updated_at'=>now(),'created_at'=>now()]);
    }
    public function query(string $namespace,array $vector,int $limit=10,array $filter=[]):array
    {
        // Works with pgvector when embedding is migrated to vector; falls back to in-PHP cosine for JSON storage.
        $rows=$this->db->table($this->table)->where('namespace',$namespace)->limit((int)config('agent-fabric.knowledge.vector_candidate_limit',500))->get();$out=[];
        foreach($rows as $row){$meta=json_decode((string)$row->metadata,true)?:[];if(!$this->matches($meta,$filter))continue;$stored=json_decode((string)$row->embedding,true)?:[];$out[]=['id'=>(string)$row->record_id,'score'=>$this->cosine($vector,$stored),'content'=>$row->content,'metadata'=>$meta];}
        usort($out,fn($a,$b)=>$b['score']<=>$a['score']);return array_slice($out,0,$limit);
    }
    public function delete(string $namespace,array $ids):void{$this->db->table($this->table)->where('namespace',$namespace)->whereIn('record_id',array_map('strval',$ids))->delete();}
    private function matches(array $meta,array $filter):bool{foreach($filter as $k=>$v)if(($meta[$k]??null)!==$v)return false;return true;}
    private function cosine(array $a,array $b):float{$n=min(count($a),count($b));if($n===0)return 0.0;$d=$aa=$bb=0.0;for($i=0;$i<$n;$i++){$x=(float)$a[$i];$y=(float)$b[$i];$d+=$x*$y;$aa+=$x*$x;$bb+=$y*$y;}return $aa>0&&$bb>0?$d/(sqrt($aa)*sqrt($bb)):0.0;}
}
