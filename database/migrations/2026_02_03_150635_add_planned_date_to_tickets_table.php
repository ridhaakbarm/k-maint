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
    Schema::table('tickets', function (Blueprint $table) {
        // Tambahkan kolom planned_date setelah kolom attachment
        if (!Schema::hasColumn('tickets', 'planned_date')) {
            $table->date('planned_date')->nullable()->after('attachment');
        }
    });
}

public function down()
{
    Schema::table('tickets', function (Blueprint $table) {
        $table->dropColumn('planned_date');
    });
}
};
