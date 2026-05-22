<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up() 
{
    Schema::table('pm_check_items', function (Blueprint $table) {
        // Cek dulu, kalau belum ada baru kita buat
        if (!Schema::hasColumn('pm_check_items', 'photo_after')) {
            $table->string('photo_after')->nullable()->after('photo_before');
        }
    });
}

public function down(): void
{
    Schema::table('pm_check_items', function (Blueprint $table) {
        // Jangan lupa isi ini supaya bisa di-rollback
        if (Schema::hasColumn('pm_check_items', 'photo_after')) {
            $table->dropColumn('photo_after');
        }
    });
}
};
