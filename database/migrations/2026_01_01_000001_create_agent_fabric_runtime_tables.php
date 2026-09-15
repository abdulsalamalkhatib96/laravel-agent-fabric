<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ai_runs',function(Blueprint $t){$t->uuid('id')->primary();$t->string('tenant_id')->index();$t->string('actor_type')->nullable();$t->string('actor_id')->nullable();$t->string('agent')->index();$t->string('agent_version');$t->string('status')->index();$t->longText('input');$t->longText('output')->nullable();$t->json('context')->nullable();$t->string('correlation_id')->nullable()->index();$t->string('waiting_approval_id')->nullable();$t->unsignedBigInteger('version')->default(0);$t->string('failure_code')->nullable();$t->text('failure_message')->nullable();$t->timestamp('started_at')->nullable();$t->timestamp('completed_at')->nullable();$t->timestamp('failed_at')->nullable();$t->timestamps();});
        Schema::create('ai_run_steps',function(Blueprint $t){$t->uuid('id')->primary();$t->uuid('run_id')->index();$t->unsignedInteger('sequence');$t->string('type')->index();$t->string('status');$t->longText('input')->nullable();$t->longText('output')->nullable();$t->json('metrics')->nullable();$t->timestamps();$t->unique(['run_id','sequence']);});
        Schema::create('ai_tool_executions',function(Blueprint $t){$t->uuid('id')->primary();$t->uuid('run_id')->index();$t->string('tenant_id')->index();$t->string('tool_name')->index();$t->longText('arguments')->nullable();$t->string('idempotency_key',64)->unique();$t->string('status')->index();$t->longText('result')->nullable();$t->text('message')->nullable();$t->longText('evidence')->nullable();$t->timestamp('finished_at')->nullable();$t->timestamps();});
        Schema::create('ai_approvals',function(Blueprint $t){$t->uuid('id')->primary();$t->uuid('run_id')->index();$t->string('tenant_id')->index();$t->string('tool_name');$t->longText('arguments');$t->text('reason');$t->string('status')->index();$t->string('decided_by')->nullable();$t->text('decision_reason')->nullable();$t->timestamp('decided_at')->nullable();$t->timestamps();});
        Schema::create('ai_usage',function(Blueprint $t){$t->uuid('id')->primary();$t->uuid('run_id')->index();$t->string('provider')->nullable()->index();$t->string('model')->nullable()->index();$t->unsignedBigInteger('input_tokens')->nullable();$t->unsignedBigInteger('output_tokens')->nullable();$t->decimal('cost',14,8)->default(0);$t->json('metadata')->nullable();$t->timestamps();});
    }
    public function down(): void {foreach(['ai_usage','ai_approvals','ai_tool_executions','ai_run_steps','ai_runs'] as $t)Schema::dropIfExists($t);}
};
