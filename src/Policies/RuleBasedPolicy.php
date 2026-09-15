<?php

namespace Evolvex\AgentFabric\Policies;

use Closure;
use Evolvex\AgentFabric\Contracts\AgentPolicy;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\AuthorizationDecision;

final class RuleBasedPolicy implements AgentPolicy
{
    /** @param list<array{phase:string,allow:Closure,reason:string}> $rules */
    public function __construct(private readonly array $rules){}
    public function evaluate(AgentContext $context,string $phase,array $payload=[]): AuthorizationDecision{
        foreach($this->rules as $rule){if($rule['phase']!=='*'&&$rule['phase']!==$phase)continue;if(!(bool)($rule['allow'])($context,$payload))return AuthorizationDecision::deny($rule['reason']);}
        return AuthorizationDecision::allow();
    }
}
