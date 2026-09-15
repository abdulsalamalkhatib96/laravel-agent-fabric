<?php

namespace Evolvex\AgentFabric\Events;

use Evolvex\AgentFabric\Data\AgentResult;

final readonly class AgentRunCompleted { public function __construct(public AgentResult $result) {} }
