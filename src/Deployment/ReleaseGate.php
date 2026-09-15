<?php

namespace Evolvex\AgentFabric\Deployment;

final readonly class ReleaseGate
{
    public function __construct(public array $minimums=[],public array $maximums=[]){ }
    public function evaluate(array $metrics): ReleaseGateResult{
        $fail=[];foreach($this->minimums as $k=>$v)if(($metrics[$k]??-INF)<$v)$fail[]="{$k} below minimum {$v}";foreach($this->maximums as $k=>$v)if(($metrics[$k]??INF)>$v)$fail[]="{$k} above maximum {$v}";return new ReleaseGateResult($fail===[],$fail,$metrics);
    }
}
