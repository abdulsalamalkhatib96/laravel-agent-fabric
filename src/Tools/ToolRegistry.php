<?php

namespace Evolvex\AgentFabric\Tools;

use Evolvex\AgentFabric\Contracts\AgentTool;
use InvalidArgumentException;

final class ToolRegistry
{
    /** @var array<string,AgentTool> */
    private array $tools = [];

    public function register(AgentTool $tool): void { $this->tools[$tool->name()] = $tool; }
    public function has(string $name): bool { return isset($this->tools[$name]); }
    public function get(string $name): AgentTool
    {
        return $this->tools[$name] ?? throw new InvalidArgumentException("Unknown tool [{$name}].");
    }
    /** @return list<AgentTool> */
    public function only(array $names): array { return array_values(array_filter($this->tools, fn (AgentTool $t) => in_array($t->name(), $names, true))); }
    public function all(): array { return array_values($this->tools); }
}
