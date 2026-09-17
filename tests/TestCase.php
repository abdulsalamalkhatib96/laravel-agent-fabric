<?php

namespace Evolvex\AgentFabric\Tests;

use Evolvex\AgentFabric\AgentFabricServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array{return [AgentFabricServiceProvider::class];}

    protected function defineEnvironment($app):void
    {
        $driver=getenv('AGENT_FABRIC_TEST_DB')?:'sqlite';
        $app['config']->set('database.default','testing');
        $connection=match($driver){
            'mysql'=>['driver'=>'mysql','host'=>getenv('MYSQL_HOST')?:'127.0.0.1','port'=>getenv('MYSQL_PORT')?:3306,'database'=>getenv('MYSQL_DATABASE')?:'agent_fabric','username'=>getenv('MYSQL_USER')?:'root','password'=>getenv('MYSQL_PASSWORD')?:'secret','charset'=>'utf8mb4','collation'=>'utf8mb4_unicode_ci','prefix'=>'','strict'=>true],
            'pgsql'=>['driver'=>'pgsql','host'=>getenv('PGHOST')?:'127.0.0.1','port'=>getenv('PGPORT')?:5432,'database'=>getenv('PGDATABASE')?:'agent_fabric','username'=>getenv('PGUSER')?:'postgres','password'=>getenv('PGPASSWORD')?:'secret','charset'=>'utf8','prefix'=>'','schema'=>'public','sslmode'=>'prefer'],
            default=>['driver'=>'sqlite','database'=>':memory:','prefix'=>''],
        };
        $app['config']->set('database.connections.testing',$connection);
        $app['config']->set('agent-fabric.routing.models',[
            'test'=>['provider'=>'test','model'=>'test','capabilities'=>[],'quality'=>.5],
        ]);
    }
}
