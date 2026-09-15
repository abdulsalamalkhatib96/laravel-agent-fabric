<?php

namespace Evolvex\AgentFabric\Contracts;

use Evolvex\AgentFabric\Data\ConversationEnvelope;

interface ChannelAdapter
{
    public function name(): string;
    public function normalize(mixed $payload): ConversationEnvelope;
    public function deliver(ConversationEnvelope $conversation, string $message, array $metadata = []): void;
}
