<?php

namespace Evolvex\AgentFabric\Contracts;

use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\AgentDefinition;
use Evolvex\AgentFabric\Enums\RunStatus;
use Evolvex\AgentFabric\Enums\StepType;

interface RunRepository
{
    public function create(AgentDefinition $agent, AgentContext $context, string $input): string;
    public function transition(string $runId, RunStatus $to, array $attributes = []): void;
    public function addStep(string $runId, StepType $type, array $input = [], array $output = [], array $metrics = []): void;
    public function isCancelled(string $runId): bool;
    public function get(string $runId): ?array;
    public function steps(string $runId): array;
}
