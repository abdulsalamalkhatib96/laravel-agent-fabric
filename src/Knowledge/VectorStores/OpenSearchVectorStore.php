<?php

namespace Evolvex\AgentFabric\Knowledge\VectorStores;

use Evolvex\AgentFabric\Contracts\VectorStore;
use Evolvex\AgentFabric\Contracts\VectorStoreTransport;

final class OpenSearchVectorStore implements VectorStore
{
    public function __construct(private readonly VectorStoreTransport $transport,private readonly string $index){}
    public function upsert(string $namespace,array $records):void{foreach($records as $r)$this->transport->request('PUT','/'.$this->index.'/_doc/'.rawurlencode((string)$r['id']),['namespace'=>$namespace,'vector'=>$r['vector'],'content'=>$r['content']??null,'metadata'=>$r['metadata']??[]]);}
    public function query(string $namespace,array $vector,int $limit=10,array $filter=[]):array{$must=[['term'=>['namespace'=>$namespace]]];foreach($filter as $k=>$v)$must[]=['term'=>['metadata.'.$k=>$v]];$r=$this->transport->request('POST','/'.$this->index.'/_search',['size'=>$limit,'query'=>['knn'=>['vector'=>['vector'=>$vector,'k'=>$limit,'filter'=>['bool'=>['must'=>$must]]]]]]);return array_map(fn($x)=>['id'=>(string)($x['_id']??''),'score'=>(float)($x['_score']??0),'content'=>$x['_source']['content']??null,'metadata'=>$x['_source']['metadata']??[]],$r['hits']['hits']??[]);}
    public function delete(string $namespace,array $ids):void{foreach($ids as $id)$this->transport->request('DELETE','/'.$this->index.'/_doc/'.rawurlencode((string)$id));}
}
