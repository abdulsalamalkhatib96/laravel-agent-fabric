<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ai_feedback',function(Blueprint $t){$t->uuid('id')->primary();$t->uuid('run_id')->index();$t->string('tenant_id')->index();$t->smallInteger('rating')->nullable();$t->string('label')->nullable();$t->text('reason')->nullable();$t->longText('corrected_answer')->nullable();$t->timestamps();});
        Schema::create('ai_eval_datasets',function(Blueprint $t){$t->uuid('id')->primary();$t->string('name')->unique();$t->text('description')->nullable();$t->timestamps();});
        Schema::create('ai_eval_cases',function(Blueprint $t){$t->uuid('id')->primary();$t->uuid('dataset_id')->index();$t->string('name');$t->longText('input');$t->json('context')->nullable();$t->json('expectations')->nullable();$t->timestamps();});
        Schema::create('ai_eval_runs',function(Blueprint $t){$t->uuid('id')->primary();$t->uuid('dataset_id')->index();$t->string('agent')->index();$t->string('agent_version');$t->string('status');$t->json('summary')->nullable();$t->timestamp('started_at')->nullable();$t->timestamp('completed_at')->nullable();$t->timestamps();});
        Schema::create('ai_eval_results',function(Blueprint $t){$t->uuid('id')->primary();$t->uuid('eval_run_id')->index();$t->uuid('case_id')->index();$t->boolean('passed');$t->decimal('score',5,4)->default(0);$t->longText('answer')->nullable();$t->json('details')->nullable();$t->unsignedInteger('latency_ms')->nullable();$t->decimal('cost',14,8)->default(0);$t->timestamps();});
    }
    public function down(): void {foreach(['ai_eval_results','ai_eval_runs','ai_eval_cases','ai_eval_datasets','ai_feedback'] as $t)Schema::dropIfExists($t);}
};
