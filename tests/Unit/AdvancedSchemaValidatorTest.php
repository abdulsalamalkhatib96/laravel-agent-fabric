<?php

namespace Evolvex\AgentFabric\Tests\Unit;

use Evolvex\AgentFabric\Tools\SchemaValidator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AdvancedSchemaValidatorTest extends TestCase
{
    public function test_nested_json_schema_constraints_are_enforced():void
    {
        $schema=['type'=>'object','additionalProperties'=>false,'required'=>['email','items'],'properties'=>[
            'email'=>['type'=>'string','format'=>'email'],
            'items'=>['type'=>'array','minItems'=>1,'uniqueItems'=>true,'items'=>['type'=>'integer','minimum'=>1]],
            'mode'=>['anyOf'=>[['type'=>'string','enum'=>['safe']],['type'=>'null']]],
        ]];
        (new SchemaValidator)->validate($schema,['email'=>'a@example.com','items'=>[1,2],'mode'=>'safe']);
        self::assertTrue(true);
    }

    public function test_unknown_properties_are_rejected():void
    {
        $this->expectException(InvalidArgumentException::class);
        (new SchemaValidator)->validate(['type'=>'object','additionalProperties'=>false,'properties'=>['id'=>['type'=>'string']]],['id'=>'x','secret'=>'no']);
    }
}
