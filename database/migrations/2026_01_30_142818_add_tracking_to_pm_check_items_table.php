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
        $table->timestamp('checked_at')->nullable()->after('condition');
        $table->unsignedBigInteger('checked_by_user_id')->nullable()->after('checked_at');
        
        // Opsional: Hubungkan ke tabel users
        $table->foreign('checked_by_user_id')->references('id')->on('users')->onDelete('set null');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pm_check_items', function (Blueprint $table) {
            //
        });
    }
};
