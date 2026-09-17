<?php

namespace Evolvex\AgentFabric\Tests\Integration\Concurrency;

use Evolvex\AgentFabric\Reliability\DatabaseLeaseManager;
use Evolvex\AgentFabric\Tests\TestCase;
use Illuminate\Database\SQLiteConnection;
use PDO;

final class LeaseRaceTest extends TestCase
{
    public function test_only_one_process_can_claim_the_same_live_lease():void
    {
        if(!function_exists('pcntl_fork')||!extension_loaded('pdo_sqlite'))$this->markTestSkipped('pcntl and pdo_sqlite are required for the process-level race test.');
        $dbFile=tempnam(sys_get_temp_dir(),'agent-fabric-race-');$results=$dbFile.'.results';$start=$dbFile.'.start';
        $pdo=new PDO('sqlite:'.$dbFile);$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$pdo->exec('PRAGMA journal_mode=WAL; PRAGMA busy_timeout=5000;');
        $pdo->exec('CREATE TABLE ai_leases (scope VARCHAR(255) NOT NULL, lease_key VARCHAR(255) NOT NULL, owner VARCHAR(255) NOT NULL, expires_at DATETIME NOT NULL, created_at DATETIME, updated_at DATETIME, PRIMARY KEY(scope, lease_key))');unset($pdo);
        $children=[];
        for($i=0;$i<4;$i++){
            $pid=pcntl_fork();
            if($pid===0){while(!file_exists($start))usleep(1000);$childPdo=new PDO('sqlite:'.$dbFile);$childPdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$childPdo->exec('PRAGMA busy_timeout=5000;');$connection=new SQLiteConnection($childPdo);$lease=new DatabaseLeaseManager($connection);$ok=$lease->acquire('workflow-step','same','worker-'.$i,5);file_put_contents($results,($ok?'1':'0')."\n",FILE_APPEND|LOCK_EX);usleep(100000);exit(0);}
            $children[]=$pid;
        }
        touch($start);foreach($children as $pid)pcntl_waitpid($pid,$status);
        $wins=array_filter(file($results,FILE_IGNORE_NEW_LINES)?:[],fn($v)=>$v==='1');
        @unlink($results);@unlink($start);@unlink($dbFile);
        self::assertCount(1,$wins,'Exactly one process must own a non-expired lease.');
    }
}
