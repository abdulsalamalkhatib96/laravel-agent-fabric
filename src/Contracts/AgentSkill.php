<?php

namespace Evolvex\AgentFabric\Contracts;

interface AgentSkill
{
    public function name(): string;
    public function instructions(): string;
    public function tools(): array;
    public function knowledgeSources(): array;
    public function policies(): array;
}
