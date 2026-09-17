<?php

namespace Evolvex\AgentFabric\Workflow;

use Evolvex\AgentFabric\Enums\WorkflowStepType;
use InvalidArgumentException;

final class WorkflowDefinition
{
    /** @var array<string, WorkflowStep> */
    private array $steps=[];
    public function __construct(public readonly string $name, public readonly string $version='1.0.0') {}

    public function step(string $name,string $handler,array $dependsOn=[],array $input=[],?string $compensation=null,array $metadata=[]):self{return $this->add(new WorkflowStep($name,WorkflowStepType::Action,$handler,$dependsOn,$input,$compensation,$metadata));}
    public function verify(string $name,string $handler,array $dependsOn=[],array $metadata=[]):self{return $this->add(new WorkflowStep($name,WorkflowStepType::Verify,$handler,$dependsOn,[],null,$metadata));}
    public function decision(string $name,string $handler,array $dependsOn=[],array $input=[],array $metadata=[]):self{return $this->add(new WorkflowStep($name,WorkflowStepType::Decision,$handler,$dependsOn,$input,null,$metadata));}
    public function delay(string $name,int $seconds,array $dependsOn=[]):self{return $this->add(new WorkflowStep($name,WorkflowStepType::Delay,null,$dependsOn,[],null,['seconds'=>max(1,$seconds)]));}
    public function human(string $name,array $dependsOn=[],array $input=[],array $metadata=[]):self{return $this->add(new WorkflowStep($name,WorkflowStepType::Human,null,$dependsOn,$input,null,$metadata));}
    public function approval(string $name,array $dependsOn=[],array $input=[],array $metadata=[]):self{return $this->add(new WorkflowStep($name,WorkflowStepType::Approval,null,$dependsOn,$input,null,$metadata));}
    public function add(WorkflowStep $step):self{if(isset($this->steps[$step->name]))throw new InvalidArgumentException("Duplicate workflow step [{$step->name}].");$this->steps[$step->name]=$step;return $this;}
    public function steps():array{return array_values($this->steps);}
    public function get(string $name):WorkflowStep{return $this->steps[$name]??throw new InvalidArgumentException("Unknown workflow step [{$name}].");}
}
