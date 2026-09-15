<?php

namespace Evolvex\AgentFabric\Contracts;

use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\ConnectorAction;
use Evolvex\AgentFabric\Data\ConnectorQuery;
use Evolvex\AgentFabric\Data\ConnectorResult;
use Evolvex\AgentFabric\Data\ConnectorSchema;

interface Connector
{
    public function name(): string;
    public function schema(): ConnectorSchema;
    public function query(AgentContext $context, ConnectorQuery $query): ConnectorResult;
    public function execute(AgentContext $context, ConnectorAction $action): ConnectorResult;
}
