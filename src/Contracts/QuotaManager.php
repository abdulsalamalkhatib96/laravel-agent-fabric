<?php

namespace Evolvex\AgentFabric\Contracts;

interface QuotaManager
{
    public function consume(string $scope, string $key, int $amount = 1, ?int $limit = null, ?int $windowSeconds = null): bool;
    public function remaining(string $scope, string $key, ?int $limit = null, ?int $windowSeconds = null): int;
}
