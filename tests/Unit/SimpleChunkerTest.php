<?php

namespace Evolvex\AgentFabric\Tests\Unit;

use Evolvex\AgentFabric\Knowledge\SimpleChunker;
use PHPUnit\Framework\TestCase;

final class SimpleChunkerTest extends TestCase
{
    public function test_chunks_overlap_and_preserve_content(): void
    {
        $chunks=(new SimpleChunker(100,20))->chunk(str_repeat('abcdef ghijkl mnopqr stuvwx ',10));
        self::assertGreaterThan(1,count($chunks));
        self::assertSame(0,$chunks[0]->number);
        self::assertNotSame('',$chunks[0]->content);
    }
}
