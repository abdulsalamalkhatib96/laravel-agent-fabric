<?php

namespace Evolvex\AgentFabric\Data;

use Evolvex\AgentFabric\Enums\VerificationStatus;

final readonly class VerificationResult
{
    public function __construct(
        public VerificationStatus $status,
        public float $score,
        public array $issues = [],
        public array $evidence = [],
    ) {}

    public function passed(): bool
    {
        return in_array($this->status, [VerificationStatus::Verified, VerificationStatus::PartiallyVerified], true);
    }
}
