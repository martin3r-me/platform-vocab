<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vocab_user_settings')) {
            return;
        }

        Schema::create('vocab_user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->integer('daily_goal')->default(10);
            $table->boolean('auto_play_tts')->default(true);
            $table->boolean('keyboard_shortcuts')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'team_id'], 'vocab_user_settings_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vocab_user_settings');
    }
};
