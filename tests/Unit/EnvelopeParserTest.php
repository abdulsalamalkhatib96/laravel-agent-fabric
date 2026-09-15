<?php

namespace Evolvex\AgentFabric\Tests\Unit;

use Evolvex\AgentFabric\Runtime\Protocol\EnvelopeParser;
use PHPUnit\Framework\TestCase;

final class EnvelopeParserTest extends TestCase
{
    public function test_it_parses_tool_envelopes(): void
    {
        $result=(new EnvelopeParser)->parse('{"type":"tool","tool":"find_order","arguments":{"order_id":"1"}}');
        self::assertSame('tool',$result->type);
        self::assertSame('find_order',$result->tool);
        self::assertSame('1',$result->arguments['order_id']);
    }
}
