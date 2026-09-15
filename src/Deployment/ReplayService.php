<?php

namespace Evolvex\AgentFabric\Deployment;

use Evolvex\AgentFabric\Agents\AgentRegistry;
use Evolvex\AgentFabric\Contracts\RunRepository;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\AgentResult;
use Evolvex\AgentFabric\Runtime\AgentRuntime;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

final class ReplayService
{
    public function __construct(private readonly RunRepository $runs,private readonly AgentRegistry $agents,private readonly AgentRuntime $runtime,private readonly ConnectionInterface $db){}
    public function replay(string $sourceRunId,?string $agentName=null): AgentResult
    {
        $source=$this->runs->get($sourceRunId)??throw new \InvalidArgumentException('Unknown source run.');
        $agent=$this->agents->get($agentName??(string)$source['agent']);
        $context=new AgentContext((string)$source['tenant_id'],$source['actor_id']??null,$source['actor_type']??null,['execution_mode'=>'simulate','replay_of'=>$sourceRunId],(string)Str::uuid());
        $id=(string)Str::uuid();
        $this->db->table('ai_replay_runs')->insert(['id'=>$id,'source_run_id'=>$sourceRunId,'agent'=>$agent->name(),'agent_version'=>$agent->version(),'status'=>'running','side_effects'=>false,'created_at'=>now(),'updated_at'=>now()]);
        try{$result=$this->runtime->run($agent,$context,(string)$source['input']);$this->db->table('ai_replay_runs')->where('id',$id)->update(['status'=>'completed','result'=>json_encode(['run_id'=>$result->runId,'status'=>$result->status->value,'answer'=>$result->answer]),'updated_at'=>now()]);return $result;}
        catch(\Throwable $e){$this->db->table('ai_replay_runs')->where('id',$id)->update(['status'=>'failed','result'=>json_encode(['error'=>$e->getMessage()]),'updated_at'=>now()]);throw $e;}
    }
}
