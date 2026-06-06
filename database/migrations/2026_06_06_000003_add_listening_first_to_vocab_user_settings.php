<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vocab_user_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('vocab_user_settings', 'listening_first_default')) {
                $table->boolean('listening_first_default')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('vocab_user_settings', function (Blueprint $table) {
            if (Schema::hasColumn('vocab_user_settings', 'listening_first_default')) {
                $table->dropColumn('listening_first_default');
            }
        });
    }
};
