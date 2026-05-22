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
        Schema::create('checklist_templates', function (Blueprint $table) {
    $table->id();
    $table->foreignId('pm_schedule_id')->constrained('pm_schedules')->onDelete('cascade');
    $table->string('item_name');
    $table->string('checked_part');
    $table->string('operation_source')->nullable();
    $table->text('instructions');
    $table->text('check_standard');
    $table->integer('order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->json('active_weeks')->nullable(); // Disimpan sebagai JSON karena cast array di model
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checklist_templates');
    }
};
