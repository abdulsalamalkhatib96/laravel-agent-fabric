<?php

namespace Evolvex\AgentFabric\Security;

use Evolvex\AgentFabric\Contracts\PromptInjectionDetector;

final class HeuristicPromptInjectionDetector implements PromptInjectionDetector
{
    private array $rules=[
        'ignore_instructions'=>'/ignore\s+(?:all\s+)?(?:previous|prior|system)\s+(?:system\s+)?instructions?/i',
        'reveal_system'=>'/(?:reveal|show|print|expose).{0,30}(?:system prompt|hidden instructions|developer message)/i',
        'tool_bypass'=>'/(?:bypass|disable|override).{0,30}(?:approval|policy|authorization|safety)/i',
        'credential_request'=>'/(?:send|give|return|print).{0,30}(?:api key|secret|password|token)/i',
    ];
    public function inspect(string $text):array{$matches=[];foreach($this->rules as $rule=>$pattern)if(preg_match($pattern,$text))$matches[]=$rule;return ['risk'=>$matches===[]?'low':(count($matches)>1?'high':'medium'),'matches'=>$matches,'safe'=>$matches===[]];}
}
