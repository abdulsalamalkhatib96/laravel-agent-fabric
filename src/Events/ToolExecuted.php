<?php

namespace Evolvex\AgentFabric\Events;

use Evolvex\AgentFabric\Data\ToolResult;

final readonly class ToolExecuted { public function __construct(public string $runId,public string $tool,public ToolResult $result) {} }
