<?php

namespace Evolvex\AgentFabric\Contracts;

use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\RetrievalResult;

interface Retriever
{
    /** @return list<RetrievalResult> */
    public function retrieve(string $query, AgentContext $context, array $sources = [], int $limit = 8): array;
}
