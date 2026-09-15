<?php

namespace Evolvex\AgentFabric\Support;

final class ArrayPath
{
    public static function get(array $data, string $path, mixed $default = null): mixed
    {
        foreach (explode('.',$path) as $segment) { if (!is_array($data)||!array_key_exists($segment,$data)) return $default; $data=$data[$segment]; }
        return $data;
    }
}
