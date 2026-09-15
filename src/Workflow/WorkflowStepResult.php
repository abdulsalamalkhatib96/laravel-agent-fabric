<?php

namespace Evolvex\AgentFabric\Workflow;

final readonly class WorkflowStepResult
{
    public function __construct(public bool $success, public mixed $output=null, public ?string $message=null, public array $evidence=[], public array $statePatch=[]) {}
    public static function success(mixed $output=null,array $evidence=[],array $statePatch=[]): self { return new self(true,$output,null,$evidence,$statePatch); }
    public static function failure(string $message,mixed $output=null): self { return new self(false,$output,$message); }
}
