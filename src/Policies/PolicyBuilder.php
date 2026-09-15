<?php

namespace Evolvex\AgentFabric\Policies;

use Closure;

final class PolicyBuilder
{
    private array $rules=[];
    public static function make(): self{return new self;}
    public function allowWhen(string $phase,Closure $condition,string $reason='Policy condition failed.'): self{$this->rules[]=['phase'=>$phase,'allow'=>$condition,'reason'=>$reason];return $this;}
    public function denyWhen(string $phase,Closure $condition,string $reason='Policy denied operation.'): self{$this->rules[]=['phase'=>$phase,'allow'=>fn($ctx,$payload)=>!$condition($ctx,$payload),'reason'=>$reason];return $this;}
    public function build(): RuleBasedPolicy{return new RuleBasedPolicy($this->rules);}
}
