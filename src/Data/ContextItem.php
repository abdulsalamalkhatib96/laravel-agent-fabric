<?php

namespace Evolvex\AgentFabric\Data;

use Evolvex\AgentFabric\Enums\TrustLevel;

final readonly class ContextItem
{
    public function __construct(
        public string $content,
        public TrustLevel $trust = TrustLevel::Untrusted,
        public string $source = 'unknown',
        public array $metadata = [],
    ) {}
}
