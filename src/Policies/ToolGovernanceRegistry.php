<?php

namespace Evolvex\AgentFabric\Policies;

use Evolvex\AgentFabric\Data\AgentContext;

final class ToolGovernanceRegistry
{
    private array $rules=[];
    public function register(string $tool,array $rules):self{$this->rules[$tool]=array_merge($this->rules[$tool]??[],$rules);return $this;}
    public function authorize(string $tool,AgentContext $context,array $arguments):?string
    {
        foreach($this->rules[$tool]??[] as $rule){if(($rule['type']??null)!=='deny')continue;if(($rule['when'])($context,$arguments))return (string)($rule['reason']??'Tool policy denied operation.');}
        return null;
    }
    public function approvalReason(string $tool,AgentContext $context,array $arguments):?string
    {
        foreach($this->rules[$tool]??[] as $rule){if(($rule['type']??null)!=='approval')continue;if(($rule['when'])($context,$arguments))return (string)($rule['reason']??'Tool policy requires approval.');}
        return null;
    }
}
