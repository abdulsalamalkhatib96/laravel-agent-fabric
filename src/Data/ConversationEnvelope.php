<?php

namespace Evolvex\AgentFabric\Data;

use Evolvex\AgentFabric\Enums\ChannelType;

final readonly class ConversationEnvelope
{
    /** @param list<InputPart> $parts */
    public function __construct(
        public ChannelType $channel,
        public string|int $tenantId,
        public array $parts,
        public string|int|null $actorId = null,
        public ?string $actorType = null,
        public ?string $conversationId = null,
        public array $metadata = [],
    ) {}

    public function text(): string
    {
        return implode("
", array_map(fn (InputPart $part) => $part->modality->value === 'text' ? $part->value : '['.$part->modality->value.'] '.$part->value, $this->parts));
    }
}
