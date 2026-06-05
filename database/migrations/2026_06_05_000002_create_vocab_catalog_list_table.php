<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vocab_catalog_list')) {
            return;
        }

        Schema::create('vocab_catalog_list', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vocab_catalog_id')->constrained('vocab_catalogs')->cascadeOnDelete();
            $table->foreignId('vocab_list_id')->constrained('vocab_lists')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['vocab_catalog_id', 'vocab_list_id'], 'vocab_catalog_list_unique');
            $table->index('vocab_list_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vocab_catalog_list');
    }
};
