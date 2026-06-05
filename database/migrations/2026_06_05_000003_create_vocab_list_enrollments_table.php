<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vocab_list_enrollments')) {
            return;
        }

        Schema::create('vocab_list_enrollments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vocab_list_id')->constrained('vocab_lists')->cascadeOnDelete();
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('last_studied_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'vocab_list_id'], 'vocab_enrollment_unique');
            $table->index(['user_id', 'last_studied_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vocab_list_enrollments');
    }
};
