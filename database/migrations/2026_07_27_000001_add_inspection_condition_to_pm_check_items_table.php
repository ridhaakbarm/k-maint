<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pm_check_items', function (Blueprint $table) {
            if (!Schema::hasColumn('pm_check_items', 'inspection_condition')) {
                $table->text('inspection_condition')->nullable()->after('checklist_template_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pm_check_items', function (Blueprint $table) {
            if (Schema::hasColumn('pm_check_items', 'inspection_condition')) {
                $table->dropColumn('inspection_condition');
            }
        });
    }
};
