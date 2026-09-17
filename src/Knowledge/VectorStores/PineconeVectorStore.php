<?php

namespace Evolvex\AgentFabric\Knowledge\VectorStores;

use Evolvex\AgentFabric\Contracts\VectorStore;
use Evolvex\AgentFabric\Contracts\VectorStoreTransport;

final class PineconeVectorStore implements VectorStore
{
    public function __construct(private readonly VectorStoreTransport $transport){}
    public function upsert(string $namespace,array $records):void{$vectors=array_map(fn($r)=>['id'=>(string)$r['id'],'values'=>$r['vector'],'metadata'=>array_replace($r['metadata']??[],['_content'=>$r['content']??null])],$records);$this->transport->request('POST','/vectors/upsert',['namespace'=>$namespace,'vectors'=>$vectors]);}
    public function query(string $namespace,array $vector,int $limit=10,array $filter=[]):array{$r=$this->transport->request('POST','/query',['namespace'=>$namespace,'vector'=>$vector,'topK'=>$limit,'includeMetadata'=>true,'filter'=>(object)$filter]);return array_map(fn($x)=>['id'=>(string)($x['id']??''),'score'=>(float)($x['score']??0),'content'=>$x['metadata']['_content']??null,'metadata'=>$x['metadata']??[]],$r['matches']??[]);}
    public function delete(string $namespace,array $ids):void{$this->transport->request('POST','/vectors/delete',['namespace'=>$namespace,'ids'=>array_values($ids)]);}
}
