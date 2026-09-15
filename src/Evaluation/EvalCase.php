<?php

namespace Evolvex\AgentFabric\Evaluation;

final readonly class EvalCase
{
    public function __construct(public string $name,public string $input,public string|int $tenantId='eval',public array $expectations=[],public array $metadata=[]){}
}
