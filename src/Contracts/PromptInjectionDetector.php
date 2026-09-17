<?php

namespace Evolvex\AgentFabric\Contracts;

interface PromptInjectionDetector
{
    public function inspect(string $text): array;
}
