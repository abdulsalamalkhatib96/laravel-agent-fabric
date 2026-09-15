<?php

namespace Evolvex\AgentFabric\Contracts;

use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Enums\MemoryKind;

interface MemoryStore
{
    public function remember(AgentContext $context, string $agent, MemoryKind $kind, string $key, mixed $value, float $confidence = 1.0, ?\DateTimeInterface $expiresAt = null): void;
    public function recall(AgentContext $context, string $agent, array $kinds = []): array;
}
