<?php

namespace Evolvex\AgentFabric\Connectors;

use Evolvex\AgentFabric\Contracts\Connector;
use InvalidArgumentException;

final class ConnectorRegistry
{
    /** @var array<string, Connector> */
    private array $connectors = [];

    public function register(Connector $connector): self { $this->connectors[$connector->name()] = $connector; return $this; }
    public function get(string $name): Connector { return $this->connectors[$name] ?? throw new InvalidArgumentException("Unknown connector [{$name}]."); }
    public function all(): array { return $this->connectors; }
}
