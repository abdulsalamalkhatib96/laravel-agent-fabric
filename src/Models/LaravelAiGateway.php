<?php

namespace Evolvex\AgentFabric\Models;

use Evolvex\AgentFabric\Contracts\ModelGateway;
use Evolvex\AgentFabric\Data\ModelProfile;
use Evolvex\AgentFabric\Data\ModelRequest;
use Evolvex\AgentFabric\Data\ModelResponse;
use Throwable;

use function Laravel\Ai\agent;

final class LaravelAiGateway implements ModelGateway
{
    public function generate(ModelRequest $request, ModelProfile $profile): ModelResponse
    {
        $response = agent(instructions: $request->system, messages: [], tools: [])->prompt(
            $request->prompt,
            provider: $profile->provider,
            model: $profile->model,
            timeout: $request->timeout ?? (int) config('agent-fabric.runtime.timeout', 120),
        );

        return new ModelResponse(
            text: (string) $response->text,
            provider: $profile->provider,
            model: $profile->model,
            inputTokens: $this->intProperty($response, ['inputTokens', 'promptTokens']),
            outputTokens: $this->intProperty($response, ['outputTokens', 'completionTokens']),
            metadata: ['response_class' => $response::class],
        );
    }

    private function intProperty(object $object, array $names): ?int
    {
        foreach ($names as $name) {
            try {
                if (isset($object->{$name}) && is_numeric($object->{$name})) return (int) $object->{$name};
            } catch (Throwable) {}
        }
        return null;
    }
}
