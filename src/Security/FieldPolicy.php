<?php

namespace Evolvex\AgentFabric\Security;

final class FieldPolicy
{
    public function __construct(public readonly array $allowed=[],public readonly array $sensitive=[],public readonly array $forbidden=[]) {}
    public function filter(array $record): array
    {
        $out=[];foreach($record as $key=>$value){if(in_array($key,$this->forbidden,true))continue;if($this->allowed!==[]&&!in_array($key,$this->allowed,true)&&!in_array($key,$this->sensitive,true))continue;$out[$key]=in_array($key,$this->sensitive,true)?'[REDACTED]':$value;}return $out;
    }
}
