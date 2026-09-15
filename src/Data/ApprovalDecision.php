<?php

namespace Evolvex\AgentFabric\Data;

final readonly class ApprovalDecision
{
    private function __construct(public bool $required, public ?string $reason = null) {}
    public static function notRequired(): self { return new self(false); }
    public static function required(string $reason): self { return new self(true, $reason); }
}
