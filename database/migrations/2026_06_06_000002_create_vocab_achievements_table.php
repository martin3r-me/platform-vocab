<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vocab_achievements')) {
            return;
        }

        Schema::create('vocab_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('code', 64);
            $table->timestamp('awarded_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'code'], 'vocab_achievement_unique');
            $table->index(['user_id', 'awarded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vocab_achievements');
    }
};
