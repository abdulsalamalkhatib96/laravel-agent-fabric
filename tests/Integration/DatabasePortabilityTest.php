<?php

namespace Evolvex\AgentFabric\Tests\Integration;

use Evolvex\AgentFabric\Tests\TestCase;
use Illuminate\Support\Facades\Schema;

final class DatabasePortabilityTest extends TestCase
{
    public function test_all_runtime_tables_migrate_on_supported_database():void
    {
        $this->artisan('migrate',['--force'=>true])->run();
        foreach(['ai_runs','ai_workflow_runs','ai_leases','ai_outbox','ai_inbox','ai_remote_operations','ai_circuit_breakers','ai_quota_counters','ai_model_observations','ai_prompt_versions','ai_vector_records','ai_delegations'] as $table)self::assertTrue(Schema::hasTable($table),"Missing table {$table}");
    }
}
