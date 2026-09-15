<?php

namespace Evolvex\AgentFabric\Tests\Unit;

use Evolvex\AgentFabric\Enums\WorkflowStepType;
use Evolvex\AgentFabric\Workflow\WorkflowDefinition;
use PHPUnit\Framework\TestCase;

final class WorkflowDefinitionTest extends TestCase
{
    public function test_workflow_builds_dependencies_and_human_gate(): void
    {
        $definition = (new WorkflowDefinition('refund'))
            ->step('validate', self::class)
            ->approval('approve', ['validate'])
            ->step('refund', self::class, ['approve']);

        self::assertCount(3, $definition->steps());
        self::assertSame(WorkflowStepType::Approval, $definition->get('approve')->type);
        self::assertSame(['approve'], $definition->get('refund')->dependsOn);
    }
}
