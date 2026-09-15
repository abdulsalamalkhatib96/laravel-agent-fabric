<?php

namespace Evolvex\AgentFabric\Deployment;

use Evolvex\AgentFabric\Agents\AgentBlueprint;
use Evolvex\AgentFabric\Enums\DeploymentMode;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use RuntimeException;

final class DatabaseDeploymentManager
{
    public function __construct(private readonly ConnectionInterface $db, private readonly AgentVersionFingerprint $fingerprint) {}

    public function register(AgentBlueprint $agent,array $components=[]): string
    {
        $definition=$agent->definition(); $fp=$this->fingerprint->make($definition,$components); $id=(string)Str::uuid();
        $this->db->table('ai_agent_versions')->updateOrInsert(['agent'=>$definition->name,'version'=>$definition->version],[
            'id'=>$id,'fingerprint'=>$fp,'manifest'=>json_encode(['definition'=>(array)$definition,'components'=>$components]),'status'=>'candidate','created_at'=>now(),'updated_at'=>now(),
        ]);
        return $fp;
    }

    public function deploy(string $agent,string $version,DeploymentMode $mode=DeploymentMode::Active,int $trafficPercent=100,?ReleaseGateResult $gate=null): string
    {
        if($gate!==null&&!$gate->passed) throw new RuntimeException('Release gate failed: '.implode('; ',$gate->failures));
        if($this->db->table('ai_agent_versions')->where(['agent'=>$agent,'version'=>$version])->doesntExist()) throw new RuntimeException('Unknown agent version.');
        if($mode===DeploymentMode::Active)$this->db->table('ai_deployments')->where('agent',$agent)->update(['status'=>'inactive','updated_at'=>now()]);
        $id=(string)Str::uuid();
        $this->db->table('ai_deployments')->insert(['id'=>$id,'agent'=>$agent,'version'=>$version,'mode'=>$mode->value,'traffic_percent'=>max(0,min(100,$trafficPercent)),'status'=>'active','metadata'=>json_encode([]),'created_at'=>now(),'updated_at'=>now()]);
        $this->db->table('ai_agent_versions')->where(['agent'=>$agent,'version'=>$version])->update(['status'=>'deployed','updated_at'=>now()]);
        return $id;
    }
}
