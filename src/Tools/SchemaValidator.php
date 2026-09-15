<?php

namespace Evolvex\AgentFabric\Tools;

use InvalidArgumentException;

final class SchemaValidator
{
    public function validate(array $schema, array $arguments): void
    {
        $required = $schema['required'] ?? [];
        foreach ($required as $key) if (! array_key_exists($key, $arguments)) throw new InvalidArgumentException("Missing required tool argument [{$key}].");
        foreach (($schema['properties'] ?? []) as $key => $rule) {
            if (! array_key_exists($key, $arguments)) continue;
            $type = $rule['type'] ?? null; $v = $arguments[$key];
            $ok = match ($type) {
                'string' => is_string($v), 'integer' => is_int($v), 'number' => is_int($v)||is_float($v),
                'boolean' => is_bool($v), 'array' => is_array($v), 'object' => is_array($v), null => true, default => true,
            };
            if (! $ok) throw new InvalidArgumentException("Tool argument [{$key}] must be of type [{$type}].");
            if (isset($rule['enum']) && ! in_array($v, $rule['enum'], true)) throw new InvalidArgumentException("Tool argument [{$key}] is not an allowed value.");
        }
    }
}
