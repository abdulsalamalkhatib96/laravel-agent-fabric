<?php

namespace Evolvex\AgentFabric\Tests\Unit;

use Evolvex\AgentFabric\Tools\SchemaValidator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SchemaValidatorTest extends TestCase
{
    public function test_rejects_missing_required_argument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new SchemaValidator)->validate(['required'=>['id'],'properties'=>['id'=>['type'=>'string']]],[]);
    }
}
