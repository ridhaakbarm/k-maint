<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pm_check_items', function (Blueprint $table) {
            // Menambahkan kolom verified_by_user_id setelah checklist_template_id
            $table->foreignId('verified_by_user_id')->nullable()
                  ->after('checklist_template_id')
                  ->constrained('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('pm_check_items', function (Blueprint $table) {
            $table->dropForeign(['verified_by_user_id']);
            $table->dropColumn('verified_by_user_id');
        });
    }
};