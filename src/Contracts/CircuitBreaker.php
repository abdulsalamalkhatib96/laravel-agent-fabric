<?php

namespace Evolvex\AgentFabric\Contracts;

interface CircuitBreaker
{
    public function allow(string $key): bool;
    public function success(string $key): void;
    public function failure(string $key, ?string $reason = null): void;
    public function state(string $key): string;
}
