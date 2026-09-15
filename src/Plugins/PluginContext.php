<?php

namespace Evolvex\AgentFabric\Plugins;

use Evolvex\AgentFabric\Agents\AgentRegistry;
use Evolvex\AgentFabric\Channels\ChannelRegistry;
use Evolvex\AgentFabric\Connectors\ConnectorRegistry;
use Evolvex\AgentFabric\Workflow\WorkflowRegistry;

final readonly class PluginContext
{
    public function __construct(public AgentRegistry $agents,public ConnectorRegistry $connectors,public ChannelRegistry $channels,public WorkflowRegistry $workflows){}
}
