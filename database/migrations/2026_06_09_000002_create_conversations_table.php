<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->string('session_id', 100);
            $table->enum('role', ['user', 'assistant']);
            $table->text('content');
            $table->string('agent_used', 50)->nullable();
            $table->string('model_used', 80)->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['session_id', 'seller_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
