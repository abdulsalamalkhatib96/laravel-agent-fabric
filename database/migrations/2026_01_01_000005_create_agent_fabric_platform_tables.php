<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_workflow_runs', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('tenant_id')->index();
            $t->string('workflow')->index();
            $t->string('version');
            $t->string('status')->index();
            $t->longText('state')->nullable();
            $t->text('failure_message')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
        });
        Schema::create('ai_workflow_steps', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('run_id')->index();
            $t->string('step_name');
            $t->string('step_type');
            $t->string('status')->index();
            $t->longText('input')->nullable();
            $t->longText('output')->nullable();
            $t->timestamps();
            $t->unique(['run_id','step_name']);
        });
        Schema::create('ai_trace_spans', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('trace_id')->index();
            $t->string('span')->index();
            $t->string('event')->index();
            $t->longText('attributes')->nullable();
            $t->decimal('duration_ms', 14, 3)->nullable();
            $t->timestamps();
        });
        Schema::create('ai_entities', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('tenant_id')->index();
            $t->string('entity_type')->index();
            $t->string('entity_id')->index();
            $t->longText('attributes')->nullable();
            $t->timestamps();
            $t->unique(['tenant_id','entity_type','entity_id']);
        });
        Schema::create('ai_entity_relations', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('tenant_id')->index();
            $t->string('from_key')->index();
            $t->string('relation')->index();
            $t->string('to_key')->index();
            $t->longText('metadata')->nullable();
            $t->timestamps();
            $t->unique(['tenant_id','from_key','relation','to_key'],'ai_entity_relation_unique');
        });
        Schema::create('ai_connector_states', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('tenant_id')->index();
            $t->string('connector')->index();
            $t->string('state_key');
            $t->longText('value')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->timestamps();
            $t->unique(['tenant_id','connector','state_key']);
        });
        Schema::create('ai_agent_versions', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('agent')->index();
            $t->string('version')->index();
            $t->string('fingerprint', 64)->index();
            $t->longText('manifest');
            $t->string('status')->default('candidate')->index();
            $t->timestamps();
            $t->unique(['agent','version']);
        });
        Schema::create('ai_replay_runs', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('source_run_id')->index();
            $t->string('agent')->index();
            $t->string('agent_version')->nullable();
            $t->string('status')->index();
            $t->boolean('side_effects')->default(false);
            $t->longText('result')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['ai_replay_runs','ai_agent_versions','ai_connector_states','ai_entity_relations','ai_entities','ai_trace_spans','ai_workflow_steps','ai_workflow_runs'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
