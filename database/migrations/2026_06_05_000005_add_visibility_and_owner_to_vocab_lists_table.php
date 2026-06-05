<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vocab_lists', function (Blueprint $table) {
            if (!Schema::hasColumn('vocab_lists', 'visibility')) {
                $table->string('visibility', 20)->default('team');
            }
            if (!Schema::hasColumn('vocab_lists', 'created_by_user_id')) {
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('vocab_lists', function (Blueprint $table) {
            if (Schema::hasColumn('vocab_lists', 'created_by_user_id')) {
                $table->dropConstrainedForeignId('created_by_user_id');
            }
            if (Schema::hasColumn('vocab_lists', 'visibility')) {
                $table->dropColumn('visibility');
            }
        });
    }
};
