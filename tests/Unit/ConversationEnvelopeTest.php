<?php

namespace Evolvex\AgentFabric\Tests\Unit;

use Evolvex\AgentFabric\Data\ConversationEnvelope;
use Evolvex\AgentFabric\Data\InputPart;
use Evolvex\AgentFabric\Enums\ChannelType;
use PHPUnit\Framework\TestCase;

final class ConversationEnvelopeTest extends TestCase
{
    public function test_multimodal_envelope_normalizes_to_textual_runtime_input(): void
    {
        $envelope = new ConversationEnvelope(ChannelType::WhatsApp, 'tenant', [InputPart::text('Inspect this'), InputPart::image('/tmp/damage.jpg', 'image/jpeg')], '42', 'customer');
        self::assertStringContainsString('Inspect this', $envelope->text());
        self::assertStringContainsString('[image] /tmp/damage.jpg', $envelope->text());
    }
}
