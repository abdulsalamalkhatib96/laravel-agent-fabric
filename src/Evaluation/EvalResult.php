<?php

namespace Evolvex\AgentFabric\Evaluation;

final readonly class EvalResult
{
    public function __construct(public string $case,public bool $passed,public float $score,public ?string $answer,array $details=[]){$this->details=$details;}
    public array $details;
}
