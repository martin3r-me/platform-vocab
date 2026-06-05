<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vocab_entries')) {
            return;
        }

        Schema::create('vocab_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('vocab_list_id')->constrained('vocab_lists')->cascadeOnDelete();
            $table->string('term');
            $table->string('translation');
            $table->string('gender', 5)->nullable();
            $table->string('plural')->nullable();
            $table->string('word_type', 30)->nullable();
            $table->text('example_sentence')->nullable();
            $table->text('notes')->nullable();
            $table->string('pronunciation')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vocab_entries');
    }
};
