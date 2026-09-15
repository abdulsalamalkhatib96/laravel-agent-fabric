<?php

namespace Evolvex\AgentFabric\Contracts;

interface Embedder
{
    /** @return list<array<int,float>> */
    public function embed(array $inputs): array;
}
