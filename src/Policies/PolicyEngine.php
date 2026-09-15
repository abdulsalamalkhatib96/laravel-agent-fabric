<?php

namespace Evolvex\AgentFabric\Policies;

use Evolvex\AgentFabric\Contracts\AgentPolicy;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Exceptions\PolicyViolationException;

final class PolicyEngine
{
    public function enforce(array $policies, AgentContext $context, string $phase, array $payload = []): void
    {
        foreach ($policies as $policy) {
            if (is_string($policy)) $policy = app($policy);
            if (! $policy instanceof AgentPolicy) continue;
            $decision = $policy->evaluate($context, $phase, $payload);
            if (! $decision->allowed) throw new PolicyViolationException($decision->reason ?? "Policy denied phase [{$phase}].");
        }
    }
}
