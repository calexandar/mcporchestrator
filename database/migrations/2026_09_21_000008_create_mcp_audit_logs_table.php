<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mcp_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mcp_token_id')->nullable()->constrained('mcp_tokens')->cascadeOnDelete();
            $table->string('operation_id', 255)->nullable();
            $table->string('tool_name');
            $table->string('action');
            $table->string('scope', 50)->nullable();
            $table->string('input_hash', 64)->nullable();
            $table->boolean('success');
            $table->string('error_code', 50)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['tool_name', 'created_at']);
            $table->index(['success', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mcp_audit_logs');
    }
};
