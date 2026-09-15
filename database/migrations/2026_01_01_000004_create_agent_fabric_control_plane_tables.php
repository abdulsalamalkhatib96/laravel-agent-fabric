<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ai_model_metrics',function(Blueprint $t){$t->id();$t->string('provider')->index();$t->string('model')->index();$t->decimal('success_rate',7,6)->nullable();$t->decimal('tool_accuracy',7,6)->nullable();$t->unsignedInteger('p95_latency_ms')->nullable();$t->decimal('avg_cost',14,8)->nullable();$t->json('metadata')->nullable();$t->timestamps();$t->unique(['provider','model']);});
        Schema::create('ai_deployments',function(Blueprint $t){$t->uuid('id')->primary();$t->string('agent')->index();$t->string('version');$t->string('mode')->default('active');$t->unsignedTinyInteger('traffic_percent')->default(100);$t->string('status')->default('active');$t->json('metadata')->nullable();$t->timestamps();});
        Schema::create('ai_audit_logs',function(Blueprint $t){$t->uuid('id')->primary();$t->string('tenant_id')->index();$t->uuid('run_id')->nullable()->index();$t->string('event')->index();$t->string('actor_type')->nullable();$t->string('actor_id')->nullable();$t->json('payload')->nullable();$t->string('correlation_id')->nullable()->index();$t->timestamps();});
    }
    public function down(): void {foreach(['ai_audit_logs','ai_deployments','ai_model_metrics'] as $t)Schema::dropIfExists($t);}
};
