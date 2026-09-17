<?php

namespace Evolvex\AgentFabric\Contracts;

interface Outbox
{
    public function add(string $topic, array $payload, ?string $deduplicationKey = null, ?string $tenantId = null, ?string $availableAt = null): string;
    public function claim(string $worker, int $limit = 100, int $leaseSeconds = 60): array;
    public function acknowledge(string $id, string $worker): void;
    public function fail(string $id, string $worker, string $message, int $retryAfterSeconds = 60): void;
}
