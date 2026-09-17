<?php

namespace Evolvex\AgentFabric\Knowledge;

use Evolvex\AgentFabric\Contracts\Embedder;
use Evolvex\AgentFabric\Contracts\KnowledgeAccessPolicy;
use Evolvex\AgentFabric\Contracts\PromptInjectionDetector;
use Evolvex\AgentFabric\Contracts\Retriever;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\RetrievalResult;
use Illuminate\Database\ConnectionInterface;

final class DatabaseHybridRetriever implements Retriever
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly Embedder $embedder,
        private readonly KnowledgeAccessPolicy $access,
        private readonly PromptInjectionDetector $injection,
    ) {}

    public function retrieve(string $query, AgentContext $context, array $sources = [], int $limit = 8): array
    {
        $candidateLimit=(int)config('agent-fabric.knowledge.candidate_limit',250);
        $q=$this->db->table('ai_knowledge_chunks as c')->join('ai_knowledge_documents as d','d.id','=','c.document_id')
            ->where('c.tenant_id',(string)$context->tenantId)
            ->where('d.lifecycle_status','active')
            ->where(function($q){$q->whereNull('d.effective_from')->orWhere('d.effective_from','<=',now());})
            ->where(function($q){$q->whereNull('d.effective_until')->orWhere('d.effective_until','>',now());})
            ->select(['c.id as chunk_id','c.document_id','c.content','c.embedding','c.metadata','d.source_type','d.title','d.metadata as document_metadata','d.source_updated_at','d.acl','d.security_level']);
        if($sources!==[])$q->whereIn('d.source_type',$sources);
        $rows=$q->orderByDesc('c.updated_at')->limit($candidateLimit)->get();
        if($rows->isEmpty())return [];
        $vector=$this->embedder->embed([$query])[0]??[];$terms=$this->terms($query);$results=[];
        foreach($rows as $row){
            $docMeta=$row->document_metadata?json_decode((string)$row->document_metadata,true):[];
            $acl=$row->acl?json_decode((string)$row->acl,true):[];$docMeta['_acl']=is_array($acl)?$acl:[];
            if(!$this->access->allows($context,$docMeta))continue;
            $stored=$row->embedding?json_decode((string)$row->embedding,true):[];
            $semantic=is_array($stored)&&$stored!==[]&&$vector!==[]?$this->cosine($vector,$stored):0.0;
            $lexical=$this->bm25Like($terms,(string)$row->content);
            $authority=(float)($docMeta['_provenance']['authority']??0.5);
            $risk=$this->injection->inspect((string)$row->content);
            $penalty=($risk['risk']??'low')==='high'?.30:(($risk['risk']??'low')==='medium'?.12:0.0);
            $score=max(0.0,min(1.0,($semantic*.60)+($lexical*.22)+($authority*.18)-$penalty));
            $results[]=new RetrievalResult((string)$row->content,$score,(string)$row->document_id,(string)$row->chunk_id,[
                'source_type'=>$row->source_type,'title'=>$row->title,'authority'=>$authority,'provenance'=>$docMeta['_provenance']??null,
                'source_updated_at'=>$row->source_updated_at,'security_level'=>$row->security_level,'injection_risk'=>$risk,
            ]);
        }
        usort($results,fn($a,$b)=>$b->score<=>$a->score);
        return array_slice($results,0,$limit);
    }

    private function terms(string $q):array{preg_match_all('/[\pL\pN]{2,}/u',mb_strtolower($q),$m);return array_values(array_unique($m[0]??[]));}
    private function bm25Like(array $terms,string $text):float
    {
        if($terms===[])return 0.0;$tokens=$this->terms($text);if($tokens===[])return 0.0;$freq=array_count_values($tokens);$score=0.0;$len=count($tokens);
        foreach($terms as $term){$tf=$freq[$term]??0;if($tf===0)continue;$score+=(($tf*2.2)/($tf+1.2*(.25+.75*($len/200))));}
        return min(1.0,$score/max(1,count($terms)));
    }
    private function cosine(array $a,array $b):float{$n=min(count($a),count($b));if($n===0)return 0.0;$dot=$aa=$bb=0.0;for($i=0;$i<$n;$i++){$x=(float)$a[$i];$y=(float)$b[$i];$dot+=$x*$y;$aa+=$x*$x;$bb+=$y*$y;}return $aa<=0||$bb<=0?0.0:max(0.0,$dot/(sqrt($aa)*sqrt($bb)));}
}
