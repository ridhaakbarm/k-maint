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
        // Cek dulu untuk keamanan agar tidak duplicate
        if (!Schema::hasColumn('pm_check_items', 'follow_up_status')) {
            $table->string('follow_up_status')->default('On Progress')->after('next_action');
        }
    });
}

public function down()
{
    Schema::table('pm_check_items', function (Blueprint $table) {
        if (Schema::hasColumn('pm_check_items', 'follow_up_status')) {
            $table->dropColumn('follow_up_status');
        }
    });
}
};
