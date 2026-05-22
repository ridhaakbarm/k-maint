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
        // Menambahkan kolom-kolom baru sesuai request kawan
        $table->text('follow_up_note')->nullable()->after('next_action');
        $table->date('execution_date')->nullable()->after('follow_up_note');
        $table->string('executed_by')->nullable()->after('execution_date');
        $table->string('verified_by')->nullable()->after('executed_by');
        $table->string('approved_by')->nullable()->after('verified_by');
        $table->text('remark')->nullable()->after('approved_by');
    });
}

public function down()
{
    Schema::table('pm_check_items', function (Blueprint $table) {
        // Untuk jaga-jaga kalau mau dihapus lagi kawan
        $table->dropColumn(['follow_up_note', 'execution_date', 'executed_by', 'verified_by', 'approved_by', 'remark']);
    });
}
};
