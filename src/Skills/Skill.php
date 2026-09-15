<?php

namespace Evolvex\AgentFabric\Skills;

use Evolvex\AgentFabric\Contracts\AgentSkill;

abstract class Skill implements AgentSkill
{
    public function instructions(): string { return ''; }
    public function tools(): array { return []; }
    public function knowledgeSources(): array { return []; }
    public function policies(): array { return []; }
}
