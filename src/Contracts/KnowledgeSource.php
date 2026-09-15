<?php

namespace Evolvex\AgentFabric\Contracts;

use Evolvex\AgentFabric\Data\KnowledgeDocument;

interface KnowledgeSource
{
    /** @return iterable<KnowledgeDocument> */
    public function documents(): iterable;
    public function name(): string;
}
