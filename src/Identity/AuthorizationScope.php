<?php

namespace Evolvex\AgentFabric\Identity;

final readonly class AuthorizationScope
{
    public function __construct(public array $permissions = [], public array $resources = []) {}

    public function allows(string $permission): bool
    { return in_array('*', $this->permissions, true) || in_array($permission, $this->permissions, true); }
}
