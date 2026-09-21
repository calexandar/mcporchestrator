<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mcp_token_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mcp_token_id')->constrained('mcp_tokens')->cascadeOnDelete();
            $table->string('scope');
            $table->timestamps();

            $table->unique(['mcp_token_id', 'scope']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mcp_token_scopes');
    }
};
