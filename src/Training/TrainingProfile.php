<?php

namespace Evolvex\AgentFabric\Training;

final class TrainingProfile
{
    private array $knowledge=[],$examples=[],$allowedActions=[],$forbiddenActions=[],$policies=[]; private ?string $role=null,$goal=null,$tone=null; private array $languages=[];
    public static function make(string $name): self { return new self($name); }
    private function __construct(public readonly string $name) {}
    public function role(string $v): self{$c=clone $this;$c->role=$v;return $c;} public function goal(string $v): self{$c=clone $this;$c->goal=$v;return $c;}
    public function learnFrom(array $v): self{$c=clone $this;$c->knowledge=$v;return $c;} public function observeExamples(array $v): self{$c=clone $this;$c->examples=$v;return $c;}
    public function allowActions(array $v): self{$c=clone $this;$c->allowedActions=$v;return $c;} public function never(array $v): self{$c=clone $this;$c->forbiddenActions=$v;return $c;}
    public function policies(array $v): self{$c=clone $this;$c->policies=$v;return $c;} public function tone(string $v): self{$c=clone $this;$c->tone=$v;return $c;} public function languages(array $v): self{$c=clone $this;$c->languages=$v;return $c;}
    public function toArray(): array{return ['name'=>$this->name,'role'=>$this->role,'goal'=>$this->goal,'knowledge'=>$this->knowledge,'examples'=>$this->examples,'allowed_actions'=>$this->allowedActions,'forbidden_actions'=>$this->forbiddenActions,'policies'=>$this->policies,'tone'=>$this->tone,'languages'=>$this->languages];}
}
