<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vocab_entry_progress')) {
            return;
        }

        Schema::create('vocab_entry_progress', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vocab_entry_id')->constrained('vocab_entries')->cascadeOnDelete();

            // SM-2 state
            $table->decimal('ease_factor', 4, 2)->default(2.50);
            $table->integer('interval_days')->default(0);
            $table->integer('repetitions')->default(0);
            $table->timestamp('due_at')->nullable();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->tinyInteger('last_quality')->nullable();

            // Derived/cached for fast filtering
            $table->string('status', 20)->default('new');

            // Stats
            $table->integer('total_reviews')->default(0);
            $table->integer('lapses')->default(0);

            $table->timestamps();

            $table->unique(['user_id', 'vocab_entry_id'], 'vocab_progress_unique');
            $table->index(['user_id', 'due_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vocab_entry_progress');
    }
};
