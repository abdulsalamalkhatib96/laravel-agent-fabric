<?php

namespace Evolvex\AgentFabric\Contracts;

interface PiiDetector
{
    public function detect(string $text): array;
    public function redact(string $text, array $types = []): string;
}
