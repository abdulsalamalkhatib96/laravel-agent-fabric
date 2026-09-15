<?php

namespace Evolvex\AgentFabric\Runtime\Protocol;

use RuntimeException;

final class EnvelopeParser
{
    public function parse(string $text): AgentEnvelope
    {
        $text = trim($text);
        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $text, $m)) $text = $m[1];
        $data = json_decode($text, true);
        if (! is_array($data)) throw new RuntimeException('Model did not return a valid Agent Fabric JSON envelope.');
        $type = (string) ($data['type'] ?? '');
        if (! in_array($type, ['final', 'tool', 'clarify', 'escalate'], true)) throw new RuntimeException("Unsupported Agent Fabric envelope type [{$type}].");
        if ($type === 'tool' && empty($data['tool'])) throw new RuntimeException('Tool envelope is missing tool name.');
        return new AgentEnvelope(
            type: $type,
            answer: isset($data['answer']) ? (string) $data['answer'] : null,
            tool: isset($data['tool']) ? (string) $data['tool'] : null,
            arguments: is_array($data['arguments'] ?? null) ? $data['arguments'] : [],
            reason: isset($data['reason']) ? (string) $data['reason'] : null,
            memory: is_array($data['memory'] ?? null) ? $data['memory'] : [],
            citations: is_array($data['citations'] ?? null) ? array_values(array_map('strval',$data['citations'])) : [],
        );
    }
}
