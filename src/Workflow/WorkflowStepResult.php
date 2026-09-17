<?php

namespace Evolvex\AgentFabric\Workflow;

final readonly class WorkflowStepResult
{
    public function __construct(
        public bool $success,
        public mixed $output=null,
        public ?string $message=null,
        public array $evidence=[],
        public array $statePatch=[],
        public bool $ambiguous=false,
        public ?int $retryAfterSeconds=null,
    ) {}
    public static function success(mixed $output=null,array $evidence=[],array $statePatch=[]): self{return new self(true,$output,null,$evidence,$statePatch);}
    public static function failure(string $message,mixed $output=null): self{return new self(false,$output,$message);}
    public static function retry(string $message,int $afterSeconds=1,mixed $output=null): self{return new self(false,$output,$message,[],[],false,max(1,$afterSeconds));}
    public static function ambiguous(string $message,mixed $output=null,array $evidence=[]): self{return new self(false,$output,$message,$evidence,[],true,null);}
}
