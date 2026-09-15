<?php

namespace Evolvex\AgentFabric\Knowledge;

use Evolvex\AgentFabric\Contracts\Embedder;
use Laravel\Ai\Embeddings;

final class LaravelAiEmbedder implements Embedder
{
    public function embed(array $inputs): array
    {
        if ($inputs === []) return [];
        $request = Embeddings::for(array_values($inputs));
        $dimensions = (int) config('agent-fabric.knowledge.embedding_dimensions', 1536);
        if ($dimensions > 0) $request = $request->dimensions($dimensions);
        $provider = config('agent-fabric.knowledge.embedding_provider');
        $model = config('agent-fabric.knowledge.embedding_model');
        $response = $provider ? $request->generate($provider, $model) : $request->generate();
        return array_map(fn ($v) => array_map('floatval', $v), $response->embeddings);
    }
}
