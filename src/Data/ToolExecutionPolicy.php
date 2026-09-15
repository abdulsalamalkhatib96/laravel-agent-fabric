<?php

namespace Evolvex\AgentFabric\Data;

final readonly class ToolExecutionPolicy
{
    public function __construct(
        public int $timeoutSeconds = 30,
        public int $maxAttempts = 1,
        public bool $idempotent = true,
        public bool $allowAutomaticRetry = false,
        public bool $requiresReconciliationOnAmbiguity = false,
        public ?string $concurrencyKey = null,
    ) {}
}
