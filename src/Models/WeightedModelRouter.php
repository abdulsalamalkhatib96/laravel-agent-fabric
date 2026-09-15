<?php

namespace Evolvex\AgentFabric\Models;

use Evolvex\AgentFabric\Contracts\ModelRouter;
use Evolvex\AgentFabric\Data\ModelProfile;
use Evolvex\AgentFabric\Data\ModelRequest;
use RuntimeException;

final class WeightedModelRouter implements ModelRouter
{
    public function __construct(private readonly ConfigModelCatalog $catalog) {}

    public function route(ModelRequest $request): ModelProfile
    {
        $candidates = array_values(array_filter($this->catalog->all(), function (ModelProfile $profile) use ($request): bool {
            foreach ($request->requiredCapabilities as $capability) {
                if (! $profile->supports($capability)) return false;
            }
            return $profile->provider !== '' && $profile->model !== '';
        }));

        if ($candidates === []) { throw new RuntimeException('No configured AI model satisfies the requested capabilities.'); }

        $weights = config('agent-fabric.routing.weights', []);
        usort($candidates, fn (ModelProfile $a, ModelProfile $b) => $this->score($b, $weights) <=> $this->score($a, $weights));
        return $candidates[0];
    }

    private function score(ModelProfile $p, array $w): float
    {
        return $p->quality * ($w['quality'] ?? .35)
            + $p->toolAccuracy * ($w['tool_accuracy'] ?? .20)
            + $p->reliability * ($w['reliability'] ?? .15)
            + $p->latencyScore * ($w['latency'] ?? .10)
            + $p->costScore * ($w['cost'] ?? .10)
            + $p->historicalEval * ($w['historical_eval'] ?? .10);
    }
}
