<?php

namespace Evolvex\AgentFabric\Contracts;

use Evolvex\AgentFabric\Data\EntityReference;

interface EntityStore
{
    public function put(EntityReference $entity): void;
    public function relate(EntityReference $from, string $relation, EntityReference $to, array $metadata = []): void;
    public function get(string|int $tenantId, string $type, string|int $id): ?EntityReference;
    public function related(EntityReference $entity, ?string $relation = null): array;
}
