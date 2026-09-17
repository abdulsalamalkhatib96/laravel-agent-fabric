<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_leases', function (Blueprint $t) {
            $t->string('scope');
            $t->string('lease_key');
            $t->string('owner')->index();
            $t->timestamp('expires_at')->index();
            $t->timestamps();
            $t->primary(['scope', 'lease_key']);
        });

        Schema::create('ai_outbox', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('tenant_id')->nullable()->index();
            $t->string('topic')->index();
            $t->longText('payload');
            $t->string('deduplication_key', 64)->unique();
            $t->string('status')->default('pending')->index();
            $t->unsignedInteger('attempts')->default(0);
            $t->string('lease_owner')->nullable()->index();
            $t->timestamp('lease_expires_at')->nullable()->index();
            $t->timestamp('available_at')->index();
            $t->timestamp('delivered_at')->nullable();
            $t->text('last_error')->nullable();
            $t->timestamps();
        });

        Schema::create('ai_inbox', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('tenant_id')->nullable()->index();
            $t->string('consumer')->index();
            $t->string('message_id')->index();
            $t->string('status')->default('processing')->index();
            $t->text('last_error')->nullable();
            $t->timestamp('processed_at')->nullable();
            $t->timestamps();
            $t->unique(['consumer', 'message_id']);
        });

        Schema::create('ai_remote_operations', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('run_id')->index();
            $t->string('tenant_id')->index();
            $t->string('operation_name')->index();
            $t->string('idempotency_key', 64)->unique();
            $t->string('reconciliation_key')->index();
            $t->string('provider_reference')->nullable()->index();
            $t->string('status')->index();
            $t->unsignedInteger('attempts')->default(0);
            $t->longText('result')->nullable();
            $t->longText('evidence')->nullable();
            $t->json('metadata')->nullable();
            $t->text('last_error')->nullable();
            $t->timestamp('resolved_at')->nullable();
            $t->timestamps();
        });

        Schema::create('ai_circuit_breakers', function (Blueprint $t) {
            $t->string('breaker_key')->primary();
            $t->string('state')->default('closed')->index();
            $t->unsignedInteger('failures')->default(0);
            $t->text('last_failure')->nullable();
            $t->timestamp('opened_until')->nullable()->index();
            $t->timestamps();
        });

        Schema::create('ai_quota_counters', function (Blueprint $t) {
            $t->string('scope');
            $t->string('quota_key');
            $t->unsignedBigInteger('bucket');
            $t->unsignedBigInteger('consumed')->default(0);
            $t->timestamps();
            $t->primary(['scope', 'quota_key', 'bucket']);
        });

        Schema::create('ai_model_observations', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('provider')->index();
            $t->string('model')->index();
            $t->boolean('success')->index();
            $t->decimal('latency_ms', 14, 3)->nullable();
            $t->unsignedBigInteger('input_tokens')->nullable();
            $t->unsignedBigInteger('output_tokens')->nullable();
            $t->decimal('cost', 14, 8)->default(0);
            $t->string('failure_class')->nullable()->index();
            $t->timestamps();
        });

        Schema::create('ai_vector_records', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('namespace')->index();
            $t->string('record_id')->index();
            $t->longText('embedding');
            $t->longText('content')->nullable();
            $t->json('metadata')->nullable();
            $t->timestamps();
            $t->unique(['namespace', 'record_id']);
        });

        Schema::create('ai_prompt_versions', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('agent')->index();
            $t->string('version')->index();
            $t->string('hash', 64)->index();
            $t->string('status')->default('candidate')->index();
            $t->timestamp('deployed_at')->nullable();
            $t->longText('system_prompt');
            $t->longText('prompt_template')->nullable();
            $t->json('metadata')->nullable();
            $t->timestamps();
            $t->unique(['agent', 'version']);
        });

        Schema::create('ai_memory_candidates', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('tenant_id')->index();
            $t->string('actor_type')->default('');
            $t->string('actor_id')->default('');
            $t->string('agent')->index();
            $t->string('memory_key');
            $t->longText('value');
            $t->decimal('confidence', 5, 4)->default(0.5);
            $t->string('status')->default('candidate')->index();
            $t->timestamp('expires_at')->nullable();
            $t->string('decided_by')->nullable();
            $t->timestamp('decided_at')->nullable();
            $t->timestamps();
        });

        Schema::create('ai_delegations', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('tenant_id')->index();
            $t->string('from_agent')->index();
            $t->string('to_agent')->index();
            $t->string('token_hash', 64)->unique();
            $t->json('scopes');
            $t->timestamp('expires_at')->index();
            $t->timestamp('revoked_at')->nullable();
            $t->timestamps();
        });

        Schema::create('ai_mcp_servers', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('tenant_id')->nullable()->index();
            $t->string('name')->index();
            $t->string('fingerprint', 64)->index();
            $t->string('trust_level')->default('untrusted')->index();
            $t->json('allowed_tools')->nullable();
            $t->json('allowed_resources')->nullable();
            $t->unsignedInteger('timeout_seconds')->default(30);
            $t->unsignedInteger('requests_per_minute')->default(60);
            $t->boolean('enabled')->default(true)->index();
            $t->timestamps();
            $t->unique(['tenant_id', 'name']);
        });

        Schema::create('ai_cost_budgets', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('tenant_id')->nullable()->index();
            $t->string('scope')->index();
            $t->string('scope_key')->index();
            $t->string('period')->default('monthly');
            $t->decimal('soft_limit', 14, 8)->nullable();
            $t->decimal('hard_limit', 14, 8);
            $t->decimal('spent', 14, 8)->default(0);
            $t->timestamp('period_started_at')->index();
            $t->timestamps();
            $t->unique(['tenant_id', 'scope', 'scope_key', 'period']);
        });

        Schema::table('ai_workflow_runs', function (Blueprint $t) {
            $t->unsignedInteger('attempts')->default(0)->after('status');
            $t->timestamp('next_attempt_at')->nullable()->index()->after('attempts');
            $t->string('failure_code')->nullable()->after('failure_message');
            $t->timestamp('failed_at')->nullable();
            $t->timestamp('compensated_at')->nullable();
        });

        Schema::table('ai_workflow_steps', function (Blueprint $t) {
            $t->unsignedInteger('attempts')->default(0)->after('status');
            $t->timestamp('next_attempt_at')->nullable()->index()->after('attempts');
            $t->string('error_code')->nullable();
            $t->text('error_message')->nullable();
            $t->string('compensation_status')->nullable()->index();
            $t->timestamp('compensated_at')->nullable();
        });

        Schema::table('ai_knowledge_documents', function (Blueprint $t) {
            $t->string('lifecycle_status')->default('active')->index();
            $t->timestamp('effective_from')->nullable()->index();
            $t->timestamp('effective_until')->nullable()->index();
            $t->json('acl')->nullable();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('ai_knowledge_documents')) {
            Schema::table('ai_knowledge_documents', function (Blueprint $t) {
                $t->dropColumn(['lifecycle_status', 'effective_from', 'effective_until', 'acl']);
            });
        }
        if (Schema::hasTable('ai_workflow_steps')) {
            Schema::table('ai_workflow_steps', function (Blueprint $t) {
                $t->dropColumn(['attempts', 'next_attempt_at', 'error_code', 'error_message', 'compensation_status', 'compensated_at']);
            });
        }
        if (Schema::hasTable('ai_workflow_runs')) {
            Schema::table('ai_workflow_runs', function (Blueprint $t) {
                $t->dropColumn(['attempts', 'next_attempt_at', 'failure_code', 'failed_at', 'compensated_at']);
            });
        }
        foreach (['ai_cost_budgets','ai_vector_records','ai_mcp_servers','ai_delegations','ai_memory_candidates','ai_prompt_versions','ai_model_observations','ai_quota_counters','ai_circuit_breakers','ai_remote_operations','ai_inbox','ai_outbox','ai_leases'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
