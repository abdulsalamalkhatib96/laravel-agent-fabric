<?php

namespace Evolvex\AgentFabric\Identity;

final readonly class DelegatedCredential
{
    public function __construct(
        public string $type,
        public string $reference,
        public array $scopes = [],
        public ?\DateTimeImmutable $expiresAt = null,
    ) {}

    public function expired(): bool { return $this->expiresAt !== null && $this->expiresAt <= new \DateTimeImmutable(); }
}
