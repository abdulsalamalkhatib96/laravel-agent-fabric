<?php

namespace Evolvex\AgentFabric\Contracts;

interface TenantAwareKnowledgeSource
{
    /** @return iterable<string|int> */
    public function tenantIds(): iterable;
}
