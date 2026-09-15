<?php

namespace Evolvex\AgentFabric\Identity;

final readonly class ActorIdentity
{
    /** @param list<DelegatedCredential> $credentials */
    public function __construct(
        public string|int $id,
        public string $type,
        public string|int $tenantId,
        public AuthorizationScope $scope,
        public array $credentials = [],
        public array $metadata = [],
    ) {}
}
