<?php

namespace Evolvex\AgentFabric\Connectors;

use Closure;
use Evolvex\AgentFabric\Contracts\Connector;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\ConnectorAction;
use Evolvex\AgentFabric\Data\ConnectorQuery;
use Evolvex\AgentFabric\Data\ConnectorResult;
use Evolvex\AgentFabric\Data\ConnectorSchema;

final class CallbackConnector implements Connector
{
    public function __construct(private readonly string $connectorName, private readonly ConnectorSchema $connectorSchema, private readonly Closure $queryHandler, private readonly Closure $actionHandler) {}
    public function name(): string { return $this->connectorName; }
    public function schema(): ConnectorSchema { return $this->connectorSchema; }
    public function query(AgentContext $context, ConnectorQuery $query): ConnectorResult { return ($this->queryHandler)($context,$query); }
    public function execute(AgentContext $context, ConnectorAction $action): ConnectorResult { return ($this->actionHandler)($context,$action); }
}
