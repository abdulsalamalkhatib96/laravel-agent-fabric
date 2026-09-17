<?php

namespace Evolvex\AgentFabric\Tests\Integration;

use Evolvex\AgentFabric\Contracts\Workflow;
use Evolvex\AgentFabric\Contracts\WorkflowStepHandler;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Enums\WorkflowStatus;
use Evolvex\AgentFabric\Tests\TestCase;
use Evolvex\AgentFabric\Workflow\WorkflowDefinition;
use Evolvex\AgentFabric\Workflow\WorkflowEngine;
use Evolvex\AgentFabric\Workflow\WorkflowStepResult;
use Illuminate\Database\ConnectionInterface;

final class WorkflowDurabilityTest extends TestCase
{
    protected function setUp():void{parent::setUp();$this->artisan('migrate',['--force'=>true])->run();RetryOnceHandler::$calls=0;SuccessfulStepHandler::$calls=0;FailingStepHandler::$calls=0;CompensationHandler::$calls=0;}

    public function test_retry_is_persisted_and_resumable():void
    {
        $engine=$this->app->make(WorkflowEngine::class);$workflow=new RetryWorkflow;$context=new AgentContext('t');
        $first=$engine->run($workflow,$context);self::assertSame(WorkflowStatus::Retrying,$first->status);
        $this->app->make(ConnectionInterface::class)->table('ai_workflow_steps')->where('run_id',$first->runId)->update(['next_attempt_at'=>now()->subSecond()]);
        $this->app->make(ConnectionInterface::class)->table('ai_workflow_runs')->where('id',$first->runId)->update(['next_attempt_at'=>now()->subSecond()]);
        $second=$engine->tick($workflow,$context,$first->runId);self::assertSame(WorkflowStatus::Completed,$second->status);self::assertSame(2,RetryOnceHandler::$calls);
    }

    public function test_failure_compensates_completed_steps_in_reverse_path():void
    {
        $result=$this->app->make(WorkflowEngine::class)->run(new CompensatingWorkflow,new AgentContext('t'));
        self::assertSame(WorkflowStatus::Compensated,$result->status);self::assertSame(1,SuccessfulStepHandler::$calls);self::assertSame(1,FailingStepHandler::$calls);self::assertSame(1,CompensationHandler::$calls);
    }
}

final class RetryWorkflow implements Workflow{public function name():string{return 'retry';}public function version():string{return '1';}public function definition():WorkflowDefinition{return (new WorkflowDefinition('retry'))->step('one',RetryOnceHandler::class,metadata:['max_attempts'=>3,'backoff_seconds'=>1]);}}
final class CompensatingWorkflow implements Workflow{public function name():string{return 'compensate';}public function version():string{return '1';}public function definition():WorkflowDefinition{return (new WorkflowDefinition('compensate'))->step('one',SuccessfulStepHandler::class,compensation:CompensationHandler::class)->step('two',FailingStepHandler::class,['one'],metadata:['max_attempts'=>1]);}}
final class RetryOnceHandler implements WorkflowStepHandler{public static int $calls=0;public function handle(AgentContext $context,array $input,array $state):WorkflowStepResult{self::$calls++;return self::$calls===1?WorkflowStepResult::retry('temporary',1):WorkflowStepResult::success(['ok'=>true]);}}
final class SuccessfulStepHandler implements WorkflowStepHandler{public static int $calls=0;public function handle(AgentContext $context,array $input,array $state):WorkflowStepResult{self::$calls++;return WorkflowStepResult::success(['created'=>1]);}}
final class FailingStepHandler implements WorkflowStepHandler{public static int $calls=0;public function handle(AgentContext $context,array $input,array $state):WorkflowStepResult{self::$calls++;return WorkflowStepResult::failure('boom');}}
final class CompensationHandler implements WorkflowStepHandler{public static int $calls=0;public function handle(AgentContext $context,array $input,array $state):WorkflowStepResult{self::$calls++;return WorkflowStepResult::success();}}
