<?php

namespace Evolvex\AgentFabric\Agents;

use InvalidArgumentException;

final class AgentRegistry
{
    /** @var array<string,class-string<AgentBlueprint>|AgentBlueprint> */
    private array $agents = [];

    public function register(string $name, string|AgentBlueprint $agent): void
    {
        $this->agents[$name] = $agent;
    }

    public function get(string $name): AgentBlueprint
    {
        $agent = $this->agents[$name] ?? null;
        if ($agent === null) { throw new InvalidArgumentException("Unknown agent [{$name}]."); }
        return is_string($agent) ? app($agent) : $agent;
    }

    public function all(): array { return $this->agents; }
}
