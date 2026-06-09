<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('memory_store', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->string('session_id', 100);
            $table->text('summary');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['seller_id', 'session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memory_store');
    }
};
