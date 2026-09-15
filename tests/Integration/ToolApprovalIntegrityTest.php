<?php

namespace Evolvex\AgentFabric\Tests\Integration;

use Evolvex\AgentFabric\Approvals\DatabaseApprovalManager;
use Evolvex\AgentFabric\Contracts\AgentTool;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\ToolResult;
use Evolvex\AgentFabric\Enums\ToolRisk;
use Evolvex\AgentFabric\Exceptions\ApprovalRequiredException;
use Evolvex\AgentFabric\Exceptions\ToolAuthorizationException;
use Evolvex\AgentFabric\Tests\TestCase;
use Evolvex\AgentFabric\Tools\DefaultToolAuthorizer;
use Evolvex\AgentFabric\Tools\SchemaValidator;
use Evolvex\AgentFabric\Tools\ToolExecutor;
use Evolvex\AgentFabric\Tools\ToolRegistry;
use Illuminate\Database\ConnectionInterface;

final class ToolApprovalIntegrityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->run();
    }

    public function test_approval_is_bound_to_exact_tool_arguments_and_execution_is_idempotent(): void
    {
        $db=$this->app->make(ConnectionInterface::class);
        $approvals=new DatabaseApprovalManager($db);
        $executor=new ToolExecutor(new DefaultToolAuthorizer, $approvals, $db, new SchemaValidator);
        $registry=new ToolRegistry;
        $tool=new TestFinancialTool;
        $registry->register($tool);
        $context=new AgentContext('tenant-1','customer-1');

        try {
            $executor->execute('run-1',$registry,'refund',$context,['order_id'=>'55','amount'=>100]);
            self::fail('Approval should have been required.');
        } catch (ApprovalRequiredException $e) {
            $approvalId=$e->approvalId;
        }

        $approvals->decide($approvalId,true,'finance-1');

        try {
            $executor->execute('run-1',$registry,'refund',$context,['order_id'=>'55','amount'=>999],$approvalId);
            self::fail('Changed arguments must invalidate the approval.');
        } catch (ToolAuthorizationException) {
            self::assertSame(0,$tool->executions);
        }

        $first=$executor->execute('run-1',$registry,'refund',$context,['amount'=>100,'order_id'=>'55'],$approvalId);
        $second=$executor->execute('run-1',$registry,'refund',$context,['order_id'=>'55','amount'=>100],$approvalId);

        self::assertTrue($first->success);
        self::assertTrue($second->success);
        self::assertSame(1,$tool->executions,'Idempotent replay must not execute the domain action twice.');
    }
}

final class TestFinancialTool implements AgentTool
{
    public int $executions=0;
    public function name(): string{return 'refund';}
    public function description(): string{return 'Test refund';}
    public function risk(): ToolRisk{return ToolRisk::Financial;}
    public function inputSchema(): array{return ['properties'=>['order_id'=>['type'=>'string'],'amount'=>['type'=>'number']],'required'=>['order_id','amount']];}
    public function execute(AgentContext $context,array $arguments): ToolResult{$this->executions++;return ToolResult::success(['ok'=>true],evidence:['refund:55']);}
}
