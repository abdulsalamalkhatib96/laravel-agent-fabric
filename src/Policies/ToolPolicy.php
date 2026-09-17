<?php

namespace Evolvex\AgentFabric\Policies;

use Closure;

final class ToolPolicy
{
    private array $rules=[];
    private function __construct(private readonly string $tool){}
    public static function for(string $tool):self{return new self($tool);}
    public function denyWhen(Closure $condition,string $reason='Tool policy denied operation.'):self{$this->rules[]=['type'=>'deny','when'=>$condition,'reason'=>$reason];return $this;}
    public function allowWhen(Closure $condition,string $reason='Tool policy condition failed.'):self{return $this->denyWhen(fn($ctx,$args)=>!$condition($ctx,$args),$reason);}
    public function requireApprovalWhen(Closure $condition,string $reason='Tool policy requires human approval.'):self{$this->rules[]=['type'=>'approval','when'=>$condition,'reason'=>$reason];return $this;}
    public function maxAmount(string $field,float $max,string $reason='Amount exceeds the maximum permitted by policy.'):self{return $this->denyWhen(fn($ctx,$args)=>(float)($args[$field]??0)>$max,$reason);}
    public function approvalAbove(string $field,float $threshold,string $reason='Amount requires human approval.'):self{return $this->requireApprovalWhen(fn($ctx,$args)=>(float)($args[$field]??0)>$threshold,$reason);}
    public function register(ToolGovernanceRegistry $registry):ToolGovernanceRegistry{return $registry->register($this->tool,$this->rules);}
}
