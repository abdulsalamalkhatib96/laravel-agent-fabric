<?php

namespace Evolvex\AgentFabric\Knowledge;

use Evolvex\AgentFabric\Contracts\Chunker;
use Evolvex\AgentFabric\Contracts\Embedder;
use Evolvex\AgentFabric\Contracts\KnowledgeSource;
use Evolvex\AgentFabric\Contracts\KnowledgeStore;
use Evolvex\AgentFabric\Contracts\TenantAwareKnowledgeSource;
use Evolvex\AgentFabric\Data\KnowledgeChunk;

final class KnowledgeIndexer
{
    public function __construct(private readonly Chunker $chunker,private readonly Embedder $embedder,private readonly KnowledgeStore $store) {}

    public function sync(KnowledgeSource $source): array
    {
        $count=0;$skipped=0;$seen=[];
        if($source instanceof TenantAwareKnowledgeSource)foreach($source->tenantIds() as $tenantId)$seen[(string)$tenantId]=[];
        foreach($source->documents() as $document){
            $tenant=(string)$document->tenantId;$seen[$tenant][]=$document->sourceKey;
            if($this->store->hashFor($source->name(),$tenant,$document->sourceKey)===$document->contentHash()){$skipped++;continue;}
            $chunks=$this->chunker->chunk($document->content,$document->metadata);$vectors=[];$batch=max(1,(int)config('agent-fabric.knowledge.embedding_batch_size',64));
            foreach(array_chunk($chunks,$batch) as $group){$embedded=$this->embedder->embed(array_map(fn(KnowledgeChunk $c)=>$c->content,$group));foreach($embedded as $v)$vectors[]=$v;}
            $enriched=[];foreach($chunks as $i=>$chunk)$enriched[]=new KnowledgeChunk($chunk->content,$chunk->number,$chunk->metadata,$vectors[$i]??null);
            $this->store->upsertDocument($document,$enriched);$count++;
        }
        $deleted=0;foreach($seen as $tenantId=>$keys)$deleted+=$this->store->deleteMissing($source->name(),$tenantId,$keys);
        return ['indexed'=>$count,'skipped'=>$skipped,'deleted'=>$deleted];
    }
}
