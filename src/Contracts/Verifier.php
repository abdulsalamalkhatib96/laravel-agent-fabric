<?php

namespace Evolvex\AgentFabric\Contracts;

use Evolvex\AgentFabric\Data\VerificationResult;

interface Verifier
{
    public function verify(string $answer, array $evidence, array $context = []): VerificationResult;
}
