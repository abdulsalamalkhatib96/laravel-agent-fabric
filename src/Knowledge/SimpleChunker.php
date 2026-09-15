<?php

namespace Evolvex\AgentFabric\Knowledge;

use Evolvex\AgentFabric\Contracts\Chunker;
use Evolvex\AgentFabric\Data\KnowledgeChunk;

final class SimpleChunker implements Chunker
{
    public function __construct(private readonly int $size = 1200, private readonly int $overlap = 150)
    {
        if ($this->size < 100) throw new \InvalidArgumentException('Chunk size must be >= 100.');
        if ($this->overlap < 0 || $this->overlap >= $this->size) throw new \InvalidArgumentException('Invalid chunk overlap.');
    }

    public function chunk(string $content, array $metadata = []): array
    {
        $content = trim(preg_replace('/\s+/u', ' ', $content) ?? $content);
        if ($content === '') return [];
        $chunks = []; $offset = 0; $n = 0; $length = mb_strlen($content);
        while ($offset < $length) {
            $piece = mb_substr($content, $offset, $this->size);
            if ($offset + $this->size < $length) {
                $last = mb_strrpos($piece, ' ');
                if ($last !== false && $last > (int) ($this->size * .6)) $piece = mb_substr($piece, 0, $last);
            }
            $chunks[] = new KnowledgeChunk(trim($piece), $n++, $metadata);
            $advance = max(1, mb_strlen($piece) - $this->overlap);
            $offset += $advance;
        }
        return $chunks;
    }
}
