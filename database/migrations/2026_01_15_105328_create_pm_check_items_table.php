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
        Schema::create('pm_check_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('pm_check_id')->constrained('pm_checks')->onDelete('cascade');
    $table->foreignId('checklist_template_id')->constrained('checklist_templates');
    $table->string('condition')->nullable(); // OK, Not OK, Repair
    $table->text('action_taken')->nullable();
    $table->text('next_action')->nullable();
    $table->string('photo_before')->nullable();
    $table->string('photo_after')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pm_check_items');
    }
};
