<?php

namespace Evolvex\AgentFabric\Security;

final class SecretRedactor
{
    public function redact(mixed $value): mixed
    {
        if (is_array($value)) {
            $keys=array_map('strtolower',config('agent-fabric.security.redact_keys',[])); $out=[];
            foreach ($value as $k=>$v) $out[$k]=in_array(strtolower((string)$k),$keys,true)?'***':$this->redact($v);
            return $out;
        }
        if (is_string($value)) {
            foreach (config('agent-fabric.security.redact_patterns',[]) as $pattern) $value=preg_replace($pattern,'***',$value) ?? $value;
        }
        return $value;
    }
}
