<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vocab_catalogs')) {
            return;
        }

        Schema::create('vocab_catalogs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('visibility', 20)->default('team');
            $table->string('cover_color', 7)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['team_id', 'visibility']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vocab_catalogs');
    }
};
