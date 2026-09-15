<?php

namespace Evolvex\AgentFabric\Runtime\Protocol;

final readonly class AgentEnvelope
{
    public function __construct(
        public string $type,
        public ?string $answer = null,
        public ?string $tool = null,
        public array $arguments = [],
        public ?string $reason = null,
        public array $memory = [],
        public array $citations = [],
    ) {}
}
