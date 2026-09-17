<?php

namespace Evolvex\AgentFabric\Repositories;

use Evolvex\AgentFabric\Contracts\LeaseManager;
use Evolvex\AgentFabric\Contracts\Outbox;
use Evolvex\AgentFabric\Contracts\RunRepository;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\AgentDefinition;
use Evolvex\AgentFabric\Enums\RunStatus;
use Evolvex\AgentFabric\Enums\StepType;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

final class DatabaseRunRepository implements RunRepository
{
    public function __construct(private readonly ConnectionInterface $db,private readonly LeaseManager $leases,private readonly Outbox $outbox){}

    public function create(AgentDefinition $agent,AgentContext $context,string $input):string
    {
        $id=(string)Str::uuid();$tenant=(string)$context->tenantId;
        $this->db->transaction(function()use($id,$agent,$context,$input,$tenant):void{
            $this->db->table('ai_runs')->insert(['id'=>$id,'tenant_id'=>$tenant,'actor_type'=>$context->actorType,'actor_id'=>$context->actorId===null?null:(string)$context->actorId,'agent'=>$agent->name,'agent_version'=>$agent->version,'status'=>RunStatus::Created->value,'input'=>$input,'correlation_id'=>$context->correlationId,'context'=>json_encode($context->metadata),'version'=>0,'created_at'=>now(),'updated_at'=>now()]);
            $this->outbox->add('agent-fabric.run.created',['run_id'=>$id,'agent'=>$agent->name,'version'=>$agent->version],'run:'.$id.':created',$tenant);
        });
        return $id;
    }

    public function transition(string $runId,RunStatus $to,array $attributes=[]):void
    {
        $run=$this->db->table('ai_runs')->where('id',$runId)->first();if(!$run)return;
        $payload=['status'=>$to->value,'updated_at'=>now()]+$attributes;if($to===RunStatus::Completed)$payload['completed_at']=now();if(in_array($to,[RunStatus::Failed,RunStatus::Exhausted],true))$payload['failed_at']=now();
        $this->db->transaction(function()use($runId,$to,$payload,$run):void{
            $this->db->table('ai_runs')->where('id',$runId)->increment('version',1,$payload);
            if($to->terminal())$this->outbox->add('agent-fabric.run.terminal',['run_id'=>$runId,'agent'=>$run->agent,'status'=>$to->value,'output'=>$payload['output']??null,'failure_code'=>$payload['failure_code']??null],'run:'.$runId.':'.$to->value,(string)$run->tenant_id);
        });
    }

    public function addStep(string $runId,StepType $type,array $input=[],array $output=[],array $metrics=[]):void
    {
        $owner=(string)Str::uuid();if(!$this->leases->acquire('run-step-sequence',$runId,$owner,10))throw new \RuntimeException("Could not acquire step-sequence lease for run [{$runId}].");
        try{$this->db->transaction(function()use($runId,$type,$input,$output,$metrics):void{$seq=(int)$this->db->table('ai_run_steps')->where('run_id',$runId)->max('sequence')+1;$this->db->table('ai_run_steps')->insert(['id'=>(string)Str::uuid(),'run_id'=>$runId,'sequence'=>$seq,'type'=>$type->value,'status'=>'completed','input'=>json_encode($input),'output'=>json_encode($output),'metrics'=>json_encode($metrics),'created_at'=>now(),'updated_at'=>now()]);});}
        finally{$this->leases->release('run-step-sequence',$runId,$owner);}
    }

    public function isCancelled(string $runId):bool{return $this->db->table('ai_runs')->where('id',$runId)->value('status')===RunStatus::Cancelled->value;}
    public function get(string $runId):?array{$r=$this->db->table('ai_runs')->where('id',$runId)->first();return $r?(array)$r:null;}
    public function steps(string $runId):array{return $this->db->table('ai_run_steps')->where('run_id',$runId)->orderBy('sequence')->get()->map(fn($r)=>(array)$r)->all();}
}
