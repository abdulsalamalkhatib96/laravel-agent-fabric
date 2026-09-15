<?php

namespace Evolvex\AgentFabric\Agents;

use Evolvex\AgentFabric\Contracts\AgentSkill;
use Evolvex\AgentFabric\Data\AgentDefinition;

abstract class AgentBlueprint
{
    abstract public function name(): string;
    abstract public function goal(): string;

    public function version(): string { return '1.0.0'; }
    public function instructions(): string { return ''; }
    public function tools(): array { return []; }
    public function knowledgeSources(): array { return []; }
    public function policies(): array { return []; }
    public function skills(): array { return []; }
    public function workflows(): array { return []; }
    public function connectors(): array { return []; }
    public function channels(): array { return []; }
    public function requiredCapabilities(): array { return ['reasoning', 'structured_output']; }
    public function metadata(): array { return []; }

    public function definition(): AgentDefinition
    {
        $tools=$this->tools();$knowledge=$this->knowledgeSources();$policies=$this->policies();$instructions=trim($this->instructions());
        foreach($this->skills() as $skill){
            $skill=is_string($skill)?app($skill):$skill;
            if(!$skill instanceof AgentSkill)continue;
            $tools=array_merge($tools,$skill->tools());$knowledge=array_merge($knowledge,$skill->knowledgeSources());$policies=array_merge($policies,$skill->policies());
            $text=trim($skill->instructions());if($text!=='')$instructions.="

[Skill: {$skill->name()}]
{$text}";
        }
        return new AgentDefinition(
            name:$this->name(),version:$this->version(),goal:$this->goal(),instructions:trim($instructions),
            tools:array_values(array_unique($tools,SORT_REGULAR)),knowledgeSources:array_values(array_unique($knowledge,SORT_REGULAR)),
            policies:array_values(array_unique($policies,SORT_REGULAR)),requiredCapabilities:$this->requiredCapabilities(),metadata:$this->metadata(),
            workflows:array_values(array_unique($this->workflows(),SORT_REGULAR)),connectors:array_values(array_unique($this->connectors(),SORT_REGULAR)),channels:array_values(array_unique($this->channels(),SORT_REGULAR)),
        );
    }
}
