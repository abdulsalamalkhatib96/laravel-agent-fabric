<?php

namespace Evolvex\AgentFabric\Contracts;

interface LeaseManager
{
    public function acquire(string $scope, string $key, string $owner, int $ttlSeconds = 30): bool;
    public function renew(string $scope, string $key, string $owner, int $ttlSeconds = 30): bool;
    public function release(string $scope, string $key, string $owner): void;
    public function owner(string $scope, string $key): ?string;
}
