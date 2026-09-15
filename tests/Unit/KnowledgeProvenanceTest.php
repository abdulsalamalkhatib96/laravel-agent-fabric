<?php

namespace Evolvex\AgentFabric\Tests\Unit;

use Evolvex\AgentFabric\Data\EntityReference;
use Evolvex\AgentFabric\Data\KnowledgeDocument;
use Evolvex\AgentFabric\Data\KnowledgeProvenance;
use PHPUnit\Framework\TestCase;

final class KnowledgeProvenanceTest extends TestCase
{
    public function test_document_embeds_provenance_and_entities_into_metadata(): void
    {
        $doc = new KnowledgeDocument('policy','refund','Refund','Policy text','tenant', provenance: new KnowledgeProvenance('official-policy','v4',1.0), entities: [new EntityReference('policy','refund','tenant')]);
        $meta = $doc->enrichedMetadata();
        self::assertSame(1.0, $meta['_provenance']['authority']);
        self::assertSame('policy:refund', $meta['_entities'][0]['key']);
    }
}
