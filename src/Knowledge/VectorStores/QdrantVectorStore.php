<?php

namespace Evolvex\AgentFabric\Knowledge\VectorStores;

use Evolvex\AgentFabric\Contracts\VectorStore;
use Evolvex\AgentFabric\Contracts\VectorStoreTransport;

final class QdrantVectorStore implements VectorStore
{
    public function __construct(private readonly VectorStoreTransport $transport,private readonly string $collection){}
    public function upsert(string $namespace,array $records):void{$points=array_map(fn($r)=>['id'=>(string)$r['id'],'vector'=>$r['vector'],'payload'=>array_replace($r['metadata']??[],['_namespace'=>$namespace,'_content'=>$r['content']??null])],$records);$this->transport->request('PUT',"/collections/{$this->collection}/points",['points'=>$points,'wait'=>true]);}
    public function query(string $namespace,array $vector,int $limit=10,array $filter=[]):array{$must=[['key'=>'_namespace','match'=>['value'=>$namespace]]];foreach($filter as $k=>$v)$must[]=['key'=>$k,'match'=>['value'=>$v]];$r=$this->transport->request('POST',"/collections/{$this->collection}/points/query",['query'=>$vector,'limit'=>$limit,'with_payload'=>true,'filter'=>['must'=>$must]]);return array_map(fn($x)=>['id'=>(string)($x['id']??''),'score'=>(float)($x['score']??0),'content'=>$x['payload']['_content']??null,'metadata'=>$x['payload']??[]],$r['result']['points']??[]);}
    public function delete(string $namespace,array $ids):void{$this->transport->request('POST',"/collections/{$this->collection}/points/delete",['points'=>array_values($ids),'wait'=>true]);}
}
