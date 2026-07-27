<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checklist_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('checklist_templates', 'inspection_condition')) {
                $table->string('inspection_condition')->nullable()->after('checked_part');
            }
        });
    }

    public function down(): void
    {
        Schema::table('checklist_templates', function (Blueprint $table) {
            if (Schema::hasColumn('checklist_templates', 'inspection_condition')) {
                $table->dropColumn('inspection_condition');
            }
        });
    }
};
