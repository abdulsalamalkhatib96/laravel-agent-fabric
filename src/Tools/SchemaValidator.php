<?php

namespace Evolvex\AgentFabric\Tools;

use InvalidArgumentException;

final class SchemaValidator
{
    public function validate(array $schema, mixed $value, string $path = '$'): void
    {
        if (isset($schema['oneOf'])) {
            $matches = 0;
            foreach ((array) $schema['oneOf'] as $candidate) {
                try { $this->validate($candidate, $value, $path); $matches++; } catch (InvalidArgumentException) {}
            }
            if ($matches !== 1) throw new InvalidArgumentException("{$path} must match exactly one schema.");
            return;
        }
        if (isset($schema['anyOf'])) {
            foreach ((array) $schema['anyOf'] as $candidate) {
                try { $this->validate($candidate, $value, $path); return; } catch (InvalidArgumentException) {}
            }
            throw new InvalidArgumentException("{$path} does not match any allowed schema.");
        }
        if (($schema['nullable'] ?? false) && $value === null) return;
        if (array_key_exists('const', $schema) && $value !== $schema['const']) throw new InvalidArgumentException("{$path} must equal the configured constant.");
        if (isset($schema['enum']) && ! in_array($value, (array) $schema['enum'], true)) throw new InvalidArgumentException("{$path} contains a value outside the allowed enum.");

        $type = $schema['type'] ?? null;
        if (is_array($type)) {
            foreach ($type as $candidate) {
                try { $copy=$schema; $copy['type']=$candidate; $this->validate($copy,$value,$path); return; } catch (InvalidArgumentException) {}
            }
            throw new InvalidArgumentException("{$path} has an invalid type.");
        }
        if ($type !== null) $this->assertType((string) $type, $value, $path);

        if ($type === 'object' || (is_array($value) && ! array_is_list($value))) {
            if (! is_array($value)) return;
            foreach ((array) ($schema['required'] ?? []) as $required) if (! array_key_exists($required, $value)) throw new InvalidArgumentException("{$path}.{$required} is required.");
            $properties = (array) ($schema['properties'] ?? []);
            foreach ($value as $key => $child) {
                if (isset($properties[$key])) $this->validate((array) $properties[$key], $child, "{$path}.{$key}");
                elseif (($schema['additionalProperties'] ?? true) === false) throw new InvalidArgumentException("{$path}.{$key} is not an allowed property.");
                elseif (is_array($schema['additionalProperties'] ?? null)) $this->validate($schema['additionalProperties'], $child, "{$path}.{$key}");
            }
            $count=count($value);
            if (isset($schema['minProperties']) && $count < (int)$schema['minProperties']) throw new InvalidArgumentException("{$path} has too few properties.");
            if (isset($schema['maxProperties']) && $count > (int)$schema['maxProperties']) throw new InvalidArgumentException("{$path} has too many properties.");
        }

        if ($type === 'array' && is_array($value)) {
            $count=count($value);
            if (isset($schema['minItems']) && $count < (int)$schema['minItems']) throw new InvalidArgumentException("{$path} has too few items.");
            if (isset($schema['maxItems']) && $count > (int)$schema['maxItems']) throw new InvalidArgumentException("{$path} has too many items.");
            if (($schema['uniqueItems'] ?? false) && count(array_unique(array_map('serialize',$value))) !== $count) throw new InvalidArgumentException("{$path} must contain unique items.");
            if (isset($schema['items'])) foreach ($value as $i=>$child) $this->validate((array)$schema['items'],$child,"{$path}[{$i}]");
        }

        if ($type === 'string' && is_string($value)) {
            $len=mb_strlen($value,'UTF-8');
            if (isset($schema['minLength']) && $len < (int)$schema['minLength']) throw new InvalidArgumentException("{$path} is too short.");
            if (isset($schema['maxLength']) && $len > (int)$schema['maxLength']) throw new InvalidArgumentException("{$path} is too long.");
            if (isset($schema['pattern']) && @preg_match('/'.$schema['pattern'].'/u',$value) !== 1) throw new InvalidArgumentException("{$path} does not match the required pattern.");
            if (isset($schema['format'])) $this->assertFormat((string)$schema['format'],$value,$path);
        }

        if (in_array($type,['integer','number'],true) && is_numeric($value)) {
            $number=(float)$value;
            if (isset($schema['minimum']) && $number < (float)$schema['minimum']) throw new InvalidArgumentException("{$path} is below the minimum.");
            if (isset($schema['maximum']) && $number > (float)$schema['maximum']) throw new InvalidArgumentException("{$path} is above the maximum.");
            if (isset($schema['exclusiveMinimum']) && $number <= (float)$schema['exclusiveMinimum']) throw new InvalidArgumentException("{$path} must be greater than the exclusive minimum.");
            if (isset($schema['exclusiveMaximum']) && $number >= (float)$schema['exclusiveMaximum']) throw new InvalidArgumentException("{$path} must be less than the exclusive maximum.");
        }
    }

    private function assertType(string $type, mixed $value, string $path): void
    {
        $valid=match($type){
            'object'=>is_array($value)&&!array_is_list($value),
            'array'=>is_array($value)&&array_is_list($value),
            'string'=>is_string($value),
            'integer'=>is_int($value),
            'number'=>is_int($value)||is_float($value),
            'boolean'=>is_bool($value),
            'null'=>$value===null,
            default=>true,
        };
        if(!$valid)throw new InvalidArgumentException("{$path} must be of type {$type}.");
    }

    private function assertFormat(string $format,string $value,string $path):void
    {
        $valid=match($format){
            'email'=>filter_var($value,FILTER_VALIDATE_EMAIL)!==false,
            'uri','url'=>filter_var($value,FILTER_VALIDATE_URL)!==false,
            'uuid'=>(bool)preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',$value),
            'date-time'=>strtotime($value)!==false && str_contains($value,'T'),
            'date'=>(bool)preg_match('/^\d{4}-\d{2}-\d{2}$/',$value),
            'ipv4'=>filter_var($value,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)!==false,
            'ipv6'=>filter_var($value,FILTER_VALIDATE_IP,FILTER_FLAG_IPV6)!==false,
            default=>true,
        };
        if(!$valid)throw new InvalidArgumentException("{$path} is not a valid {$format}.");
    }
}
