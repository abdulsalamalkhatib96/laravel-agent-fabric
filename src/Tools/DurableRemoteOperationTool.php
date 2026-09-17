<?php

namespace Evolvex\AgentFabric\Tools;

use Evolvex\AgentFabric\Contracts\RemoteOperationReconciler;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\RemoteOperationOutcome;
use Evolvex\AgentFabric\Data\ToolResult;
use Evolvex\AgentFabric\RemoteOperations\RemoteOperationCoordinator;

abstract class DurableRemoteOperationTool extends RemoteOperationTool
{
    public function __construct(private readonly RemoteOperationCoordinator $coordinator) {}

    final public function execute(AgentContext $context,array $arguments):ToolResult
    {
        $runId=(string)($context->metadata['run_id']??'');
        if($runId==='')throw new \RuntimeException('Durable remote operations require an Agent Fabric run id.');
        $outcome=$this->coordinator->execute(
            $runId,
            $this->operationName(),
            $this->idempotencyKey($context,$arguments),
            $this->reconciliationKey($context,$arguments),
            $context,
            fn()=> $this->send($context,$arguments),
            $this->reconciler(),
            ['tool'=>$this->name()],
        );
        if($outcome->status==='succeeded')return ToolResult::success($outcome->data,'Remote operation succeeded.',array_merge($outcome->evidence,$outcome->providerReference?['provider:'.$outcome->providerReference]:[]));
        if($outcome->status==='failed')return ToolResult::failure('Remote operation failed.',$outcome->data);
        return ToolResult::ambiguous('Remote operation outcome is ambiguous and requires reconciliation.',$outcome->data);
    }

    protected function operationName():string{return $this->name();}
    protected function idempotencyKey(AgentContext $context,array $arguments):string{return hash('sha256',(string)$context->tenantId.'|'.$this->name().'|'.$this->canonical($arguments));}
    abstract protected function reconciliationKey(AgentContext $context,array $arguments):string;
    abstract protected function send(AgentContext $context,array $arguments):RemoteOperationOutcome;
    protected function reconciler():?RemoteOperationReconciler{return null;}

    private function canonical(array $value):string{$sort=function(&$v)use(&$sort){if(!is_array($v))return;if(!array_is_list($v))ksort($v);foreach($v as &$x)$sort($x);};$sort($value);return json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}
}
