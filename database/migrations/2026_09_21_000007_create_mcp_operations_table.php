<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mcp_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mcp_token_id')->constrained('mcp_tokens')->cascadeOnDelete();
            $table->string('operation_id');
            $table->string('operation');
            $table->string('status')->default('processing');
            $table->json('result')->nullable();
            $table->timestamps();

            $table->unique(['mcp_token_id', 'operation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mcp_operations');
    }
};
