<?php

namespace Evolvex\AgentFabric\Deployment;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

final class PromptVersionStore
{
    public function __construct(private readonly ConnectionInterface $db) {}
    public function register(string $agent,string $version,string $system,string $promptTemplate='',array $metadata=[]):string
    {
        $hash=hash('sha256',$system."\n---\n".$promptTemplate);$existing=$this->db->table('ai_prompt_versions')->where(compact('agent','version'))->first();
        if($existing){$this->db->table('ai_prompt_versions')->where('id',$existing->id)->update(['hash'=>$hash,'system_prompt'=>$system,'prompt_template'=>$promptTemplate,'metadata'=>json_encode($metadata),'updated_at'=>now()]);}
        else{$this->db->table('ai_prompt_versions')->insert(['id'=>(string)Str::uuid(),'agent'=>$agent,'version'=>$version,'hash'=>$hash,'status'=>'candidate','system_prompt'=>$system,'prompt_template'=>$promptTemplate,'metadata'=>json_encode($metadata),'created_at'=>now(),'updated_at'=>now()]);}
        return $hash;
    }
    public function activate(string $agent,string $version):void
    {
        $this->db->transaction(function()use($agent,$version):void{$this->db->table('ai_prompt_versions')->where('agent',$agent)->where('status','active')->update(['status'=>'superseded','updated_at'=>now()]);$updated=$this->db->table('ai_prompt_versions')->where(compact('agent','version'))->update(['status'=>'active','deployed_at'=>now(),'updated_at'=>now()]);if($updated!==1)throw new \InvalidArgumentException("Unknown prompt version [{$agent}:{$version}].");});
    }
    public function rollback(string $agent,string $version):void{$this->activate($agent,$version);}
    public function active(string $agent):?array{$row=$this->db->table('ai_prompt_versions')->where('agent',$agent)->where('status','active')->orderByDesc('deployed_at')->first();return $row?(array)$row:null;}
    public function get(string $agent,string $version):?array{$row=$this->db->table('ai_prompt_versions')->where(compact('agent','version'))->first();return $row?(array)$row:null;}
}
