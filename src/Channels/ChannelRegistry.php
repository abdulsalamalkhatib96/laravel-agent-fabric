<?php

namespace Evolvex\AgentFabric\Channels;

use Evolvex\AgentFabric\Contracts\ChannelAdapter;
use InvalidArgumentException;

final class ChannelRegistry
{
    private array $channels=[];
    public function register(ChannelAdapter $adapter): self { $this->channels[$adapter->name()]=$adapter; return $this; }
    public function get(string $name): ChannelAdapter { return $this->channels[$name]??throw new InvalidArgumentException("Unknown channel [{$name}]."); }
    public function all(): array { return $this->channels; }
}
