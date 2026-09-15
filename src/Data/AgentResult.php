<?php

namespace Evolvex\AgentFabric\Data;

use Evolvex\AgentFabric\Enums\RunStatus;

final readonly class AgentResult
{
    public function __construct(
        public string $runId,
        public RunStatus $status,
        public ?string $answer = null,
        public array $evidence = [],
        public ?VerificationResult $verification = null,
        public array $metadata = [],
    ) {}
}
