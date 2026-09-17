<?php

namespace Evolvex\AgentFabric\Workflow;

use Evolvex\AgentFabric\Contracts\LeaseManager;
use Evolvex\AgentFabric\Contracts\Outbox;
use Evolvex\AgentFabric\Contracts\Workflow;
use Evolvex\AgentFabric\Contracts\WorkflowStepHandler;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Enums\WorkflowStatus;
use Evolvex\AgentFabric\Enums\WorkflowStepType;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use Throwable;

final class WorkflowEngine
{
    public function __construct(private readonly ConnectionInterface $db, private readonly LeaseManager $leases, private readonly Outbox $outbox) {}

    public function run(Workflow $workflow,AgentContext $context,array $input=[]):WorkflowRunResult
    {
        $runId=(string)Str::uuid();$state=['input'=>$input,'responses'=>[]];$tenant=(string)$context->tenantId;
        $this->db->transaction(function()use($runId,$tenant,$workflow,$state):void{
            $this->db->table('ai_workflow_runs')->insert(['id'=>$runId,'tenant_id'=>$tenant,'workflow'=>$workflow->name(),'version'=>$workflow->version(),'status'=>WorkflowStatus::Created->value,'state'=>json_encode($state),'attempts'=>0,'created_at'=>now(),'updated_at'=>now()]);
            $this->outbox->add('agent-fabric.workflow.created',['run_id'=>$runId,'workflow'=>$workflow->name(),'version'=>$workflow->version()],'workflow:'.$runId.':created',$tenant);
        });
        return $this->execute($workflow,$context,$runId,$state);
    }

    public function resume(Workflow $workflow,AgentContext $context,string $runId,string $stepName,mixed $response):WorkflowRunResult
    {
        $run=$this->loadRun($workflow,$context,$runId);
        if(!in_array((string)$run->status,[WorkflowStatus::Waiting->value,WorkflowStatus::Retrying->value],true))throw new \RuntimeException('Workflow is not waiting or retrying.');
        $step=$this->db->table('ai_workflow_steps')->where('run_id',$runId)->where('step_name',$stepName)->first();
        if(!$step||!in_array((string)$step->status,['waiting','retrying'],true))throw new \RuntimeException("Workflow step [{$stepName}] is not resumable.");
        $state=json_decode((string)$run->state,true)?:[];$state['responses'][$stepName]=$response;
        $this->db->table('ai_workflow_steps')->where('id',$step->id)->update(['status'=>'completed','output'=>json_encode(['response'=>$response]),'next_attempt_at'=>null,'updated_at'=>now()]);
        $this->db->table('ai_workflow_runs')->where('id',$runId)->update(['status'=>WorkflowStatus::Running->value,'state'=>json_encode($state),'next_attempt_at'=>null,'updated_at'=>now()]);
        return $this->execute($workflow,$context,$runId,$state);
    }

    public function tick(Workflow $workflow,AgentContext $context,string $runId):WorkflowRunResult
    {
        $run=$this->loadRun($workflow,$context,$runId);$state=json_decode((string)$run->state,true)?:[];
        if((string)$run->status===WorkflowStatus::Retrying->value && $run->next_attempt_at && strtotime((string)$run->next_attempt_at)>time())return new WorkflowRunResult($runId,WorkflowStatus::Retrying,$state,[],"Retry scheduled for {$run->next_attempt_at}.");
        return $this->execute($workflow,$context,$runId,$state);
    }

    public function cancel(string $runId,string|int $tenantId):void
    {
        $updated=$this->db->table('ai_workflow_runs')->where('id',$runId)->where('tenant_id',(string)$tenantId)->whereNotIn('status',[WorkflowStatus::Completed->value,WorkflowStatus::Failed->value,WorkflowStatus::Compensated->value])->update(['status'=>WorkflowStatus::Cancelled->value,'updated_at'=>now()]);
        if($updated!==1)throw new \InvalidArgumentException('Workflow run not found or already terminal for tenant.');
    }

    private function execute(Workflow $workflow,AgentContext $context,string $runId,array $state):WorkflowRunResult
    {
        $owner=(string)Str::uuid();$ttl=max(15,(int)config('agent-fabric.workflows_runtime.lease_seconds',60));
        if(!$this->leases->acquire('workflow-run',$runId,$owner,$ttl))return new WorkflowRunResult($runId,WorkflowStatus::Retrying,$state,[],'Workflow run is owned by another worker.');
        $definition=$workflow->definition();$records=[];$iterations=0;$maxSteps=(int)config('agent-fabric.workflows_runtime.max_steps',100);
        try{
            $this->db->table('ai_workflow_runs')->where('id',$runId)->update(['status'=>WorkflowStatus::Running->value,'attempts'=>$this->db->raw('attempts + 1'),'updated_at'=>now()]);
            while(true){
                $this->leases->renew('workflow-run',$runId,$owner,$ttl);
                if(++$iterations>$maxSteps)throw new \RuntimeException('Workflow exceeded maximum execution steps.');
                $status=(string)$this->db->table('ai_workflow_runs')->where('id',$runId)->value('status');
                if($status===WorkflowStatus::Cancelled->value)return new WorkflowRunResult($runId,WorkflowStatus::Cancelled,$state,$records);
                $completed=$this->db->table('ai_workflow_steps')->where('run_id',$runId)->where('status','completed')->pluck('step_name')->all();
                if(count($completed)>=count($definition->steps())){
                    $this->db->transaction(function()use($runId,$state,$workflow,$context):void{
                        $this->db->table('ai_workflow_runs')->where('id',$runId)->update(['status'=>WorkflowStatus::Completed->value,'state'=>json_encode($state),'completed_at'=>now(),'updated_at'=>now()]);
                        $this->outbox->add('agent-fabric.workflow.completed',['run_id'=>$runId,'workflow'=>$workflow->name()],'workflow:'.$runId.':completed',(string)$context->tenantId);
                    });
                    return new WorkflowRunResult($runId,WorkflowStatus::Completed,$state,$records);
                }
                $progress=false;
                foreach($definition->steps() as $step){
                    if(in_array($step->name,$completed,true)||array_diff($step->dependsOn,$completed)!==[])continue;
                    $existing=$this->db->table('ai_workflow_steps')->where('run_id',$runId)->where('step_name',$step->name)->first();
                    if($existing&&$existing->status==='waiting')return new WorkflowRunResult($runId,WorkflowStatus::Waiting,$state,$records,"Workflow waiting at [{$step->name}].");
                    if($existing&&$existing->status==='retrying'&&$existing->next_attempt_at&&strtotime((string)$existing->next_attempt_at)>time()){
                        $this->db->table('ai_workflow_runs')->where('id',$runId)->update(['status'=>WorkflowStatus::Retrying->value,'next_attempt_at'=>$existing->next_attempt_at,'state'=>json_encode($state),'updated_at'=>now()]);
                        return new WorkflowRunResult($runId,WorkflowStatus::Retrying,$state,$records,"Workflow retry scheduled for [{$step->name}].");
                    }
                    if(in_array($step->type,[WorkflowStepType::Approval,WorkflowStepType::Human],true)){
                        $this->persistStep($runId,$step,'waiting',$step->input,null);
                        $this->db->table('ai_workflow_runs')->where('id',$runId)->update(['status'=>WorkflowStatus::Waiting->value,'state'=>json_encode($state),'updated_at'=>now()]);
                        return new WorkflowRunResult($runId,WorkflowStatus::Waiting,$state,$records,"Workflow waiting at [{$step->name}].");
                    }
                    if($step->type===WorkflowStepType::Delay){
                        $seconds=max(1,(int)($step->metadata['seconds']??1));
                        if(!$existing){$this->persistStep($runId,$step,'retrying',$step->input,null,now()->addSeconds($seconds));$when=now()->addSeconds($seconds);$this->db->table('ai_workflow_runs')->where('id',$runId)->update(['status'=>WorkflowStatus::Retrying->value,'next_attempt_at'=>$when,'state'=>json_encode($state),'updated_at'=>now()]);return new WorkflowRunResult($runId,WorkflowStatus::Retrying,$state,$records,"Delay [{$step->name}] scheduled.");}
                        $this->persistStep($runId,$step,'completed',$step->input,['delayed'=>true]);$progress=true;continue;
                    }
                    $stepOwner=(string)Str::uuid();$stepLease=$runId.':'.$step->name;
                    if(!$this->leases->acquire('workflow-step',$stepLease,$stepOwner,$ttl))continue;
                    try{
                        $this->persistStep($runId,$step,'running',$step->input,null,null,true);
                        if(!$step->handler)throw new \RuntimeException("Workflow step [{$step->name}] has no handler.");
                        $handler=app($step->handler);if(!$handler instanceof WorkflowStepHandler)throw new \RuntimeException("Workflow handler [{$step->handler}] must implement WorkflowStepHandler.");
                        $result=$handler->handle($context,$step->input,$state);
                        if($result->ambiguous){$this->persistStep($runId,$step,'ambiguous',$step->input,$result->output);$this->db->transaction(function()use($runId,$result,$workflow,$context,$step):void{$this->db->table('ai_workflow_runs')->where('id',$runId)->update(['status'=>WorkflowStatus::Ambiguous->value,'failure_message'=>$result->message,'updated_at'=>now()]);$this->outbox->add('agent-fabric.workflow.ambiguous',['run_id'=>$runId,'workflow'=>$workflow->name(),'step'=>$step->name,'message'=>$result->message],'workflow:'.$runId.':ambiguous',(string)$context->tenantId);});return new WorkflowRunResult($runId,WorkflowStatus::Ambiguous,$state,$records,$result->message);}
                        if($result->retryAfterSeconds!==null){$this->scheduleRetry($runId,$step,$result->message??'Retry requested.',$result->retryAfterSeconds);return new WorkflowRunResult($runId,WorkflowStatus::Retrying,$state,$records,$result->message);}
                        if(!$result->success){
                            $attempts=(int)($this->db->table('ai_workflow_steps')->where('run_id',$runId)->where('step_name',$step->name)->value('attempts')??1);
                            $maxAttempts=max(1,(int)($step->metadata['max_attempts']??config('agent-fabric.workflows_runtime.max_attempts',3)));
                            if($attempts<$maxAttempts){$backoff=$this->backoff($attempts,$step->metadata);$this->scheduleRetry($runId,$step,$result->message??'Step failed.',$backoff);return new WorkflowRunResult($runId,WorkflowStatus::Retrying,$state,$records,$result->message);}
                            throw new \RuntimeException($result->message??"Workflow step [{$step->name}] failed.");
                        }
                        $state=array_replace_recursive($state,$result->statePatch);$state['steps'][$step->name]=$result->output;
                        $this->persistStep($runId,$step,'completed',$step->input,['output'=>$result->output,'evidence'=>$result->evidence]);
                        $this->db->table('ai_workflow_runs')->where('id',$runId)->update(['state'=>json_encode($state),'next_attempt_at'=>null,'updated_at'=>now()]);
                        $records[]=['step'=>$step->name,'status'=>'completed','output'=>$result->output];$progress=true;
                    }finally{$this->leases->release('workflow-step',$stepLease,$stepOwner);}
                }
                if(!$progress)throw new \RuntimeException('Workflow made no progress; check dependencies for cycles or unavailable steps.');
            }
        }catch(Throwable $e){
            $compensated=$this->compensate($definition,$context,$runId,$state);
            $terminal=$compensated?WorkflowStatus::Compensated:WorkflowStatus::Failed;
            $this->db->transaction(function()use($runId,$terminal,$e,$compensated,$workflow,$context):void{
                $this->db->table('ai_workflow_runs')->where('id',$runId)->update(['status'=>$terminal->value,'failure_code'=>$e::class,'failure_message'=>$e->getMessage(),'failed_at'=>now(),'compensated_at'=>$compensated?now():null,'updated_at'=>now()]);
                $this->outbox->add('agent-fabric.workflow.terminal',['run_id'=>$runId,'workflow'=>$workflow->name(),'status'=>$terminal->value,'error'=>$e->getMessage()],'workflow:'.$runId.':'.$terminal->value,(string)$context->tenantId);
            });
            return new WorkflowRunResult($runId,$terminal,$state,$records,$e->getMessage());
        }finally{$this->leases->release('workflow-run',$runId,$owner);}
    }

    private function loadRun(Workflow $workflow,AgentContext $context,string $runId):object
    {
        $run=$this->db->table('ai_workflow_runs')->where('id',$runId)->first();if(!$run)throw new \InvalidArgumentException("Unknown workflow run [{$runId}].");
        if((string)$run->tenant_id!==(string)$context->tenantId)throw new \RuntimeException('Cross-tenant workflow resume denied.');
        if((string)$run->workflow!==$workflow->name())throw new \RuntimeException('Workflow definition does not match persisted run.');
        return $run;
    }

    private function scheduleRetry(string $runId,WorkflowStep $step,string $message,int $seconds):void
    {
        $when=now()->addSeconds(max(1,$seconds));
        $this->db->table('ai_workflow_steps')->where('run_id',$runId)->where('step_name',$step->name)->update(['status'=>'retrying','next_attempt_at'=>$when,'error_message'=>$message,'updated_at'=>now()]);
        $this->db->table('ai_workflow_runs')->where('id',$runId)->update(['status'=>WorkflowStatus::Retrying->value,'next_attempt_at'=>$when,'failure_message'=>$message,'updated_at'=>now()]);
    }

    private function backoff(int $attempt,array $metadata):int
    {
        $base=max(1,(int)($metadata['backoff_seconds']??config('agent-fabric.workflows_runtime.backoff_seconds',2)));
        $max=max($base,(int)($metadata['max_backoff_seconds']??config('agent-fabric.workflows_runtime.max_backoff_seconds',60)));
        return min($max,$base*(2**max(0,$attempt-1)));
    }

    private function compensate(WorkflowDefinition $definition,AgentContext $context,string $runId,array $state):bool
    {
        if(!config('agent-fabric.workflows_runtime.compensation',true))return false;
        $completed=$this->db->table('ai_workflow_steps')->where('run_id',$runId)->where('status','completed')->pluck('step_name')->all();
        if($completed===[])return false;
        $this->db->table('ai_workflow_runs')->where('id',$runId)->update(['status'=>WorkflowStatus::Compensating->value,'updated_at'=>now()]);
        $ok=true;$attempted=false;
        foreach(array_reverse($definition->steps()) as $step){
            if(!in_array($step->name,$completed,true)||!$step->compensation)continue;
            $attempted=true;
            $handler=app($step->compensation);if(!$handler instanceof WorkflowStepHandler){$ok=false;continue;}
            try{$result=$handler->handle($context,$step->input,$state);$success=$result->success&&!$result->ambiguous;$this->db->table('ai_workflow_steps')->where('run_id',$runId)->where('step_name',$step->name)->update(['compensation_status'=>$success?'completed':'failed','compensated_at'=>$success?now():null,'updated_at'=>now()]);if(!$success)$ok=false;}catch(Throwable $e){$ok=false;$this->db->table('ai_workflow_steps')->where('run_id',$runId)->where('step_name',$step->name)->update(['compensation_status'=>'failed','error_message'=>$e->getMessage(),'updated_at'=>now()]);}
        }
        return $attempted&&$ok;
    }

    private function persistStep(string $runId,WorkflowStep $step,string $status,array $input,mixed $output,mixed $nextAttemptAt=null,bool $incrementAttempt=false):void
    {
        $existing=$this->db->table('ai_workflow_steps')->where('run_id',$runId)->where('step_name',$step->name)->first();
        $payload=['status'=>$status,'input'=>json_encode($input),'output'=>$output===null?null:json_encode($output),'next_attempt_at'=>$nextAttemptAt,'updated_at'=>now()];
        if($incrementAttempt)$payload['attempts']=$this->db->raw('attempts + 1');
        if($existing){$this->db->table('ai_workflow_steps')->where('id',$existing->id)->update($payload);return;}
        $this->db->table('ai_workflow_steps')->insert(array_merge($payload,['id'=>(string)Str::uuid(),'run_id'=>$runId,'step_name'=>$step->name,'step_type'=>$step->type->value,'attempts'=>$incrementAttempt?1:0,'created_at'=>now()]));
    }
}
