<?php

namespace Evolvex\AgentFabric\Deployment;

final readonly class ReleaseGateResult
{
    public function __construct(public bool $passed,public array $failures=[],public array $metrics=[]){ }
}
