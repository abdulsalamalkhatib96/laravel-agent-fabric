<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ai_knowledge_documents',function(Blueprint $t){$t->uuid('id')->primary();$t->string('tenant_id')->index();$t->string('source_type')->index();$t->string('source_key');$t->string('title');$t->longText('content');$t->string('content_hash',64)->index();$t->json('metadata')->nullable();$t->string('security_level')->default('internal');$t->timestamp('source_updated_at')->nullable();$t->timestamp('indexed_at')->nullable();$t->timestamps();$t->unique(['tenant_id','source_type','source_key'],'ai_kd_unique_source');});
        Schema::create('ai_knowledge_chunks',function(Blueprint $t){$t->uuid('id')->primary();$t->uuid('document_id')->index();$t->string('tenant_id')->index();$t->unsignedInteger('chunk_number');$t->longText('content');$t->longText('embedding')->nullable();$t->json('metadata')->nullable();$t->timestamps();$t->unique(['document_id','chunk_number']);});
        Schema::create('ai_memories',function(Blueprint $t){$t->uuid('id')->primary();$t->string('tenant_id')->index();$t->string('actor_type')->default('');$t->string('actor_id')->default('');$t->string('agent')->index();$t->string('kind')->index();$t->string('memory_key');$t->longText('value');$t->decimal('confidence',5,4)->default(1);$t->timestamp('expires_at')->nullable();$t->timestamps();$t->unique(['tenant_id','actor_type','actor_id','agent','kind','memory_key'],'ai_memory_identity');});
    }
    public function down(): void {foreach(['ai_memories','ai_knowledge_chunks','ai_knowledge_documents'] as $t)Schema::dropIfExists($t);}
};
