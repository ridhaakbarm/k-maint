<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->text('coordination_notes')->nullable()->after('pr_number');
            $table->string('external_vendor')->nullable()->after('coordination_notes');
            $table->text('external_notes')->nullable()->after('external_vendor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['coordination_notes', 'external_vendor', 'external_notes']);
        });
    }
};
