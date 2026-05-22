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
        Schema::create('pm_checks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('pm_schedule_id')->constrained('pm_schedules');
    $table->foreignId('technician_id')->constrained('users'); // Teknisi dari tabel users
    $table->string('technician_name');
    $table->date('check_date')->nullable();
    $table->date('due_date');
    $table->integer('week_number');
    $table->string('shift')->nullable();
    $table->string('status')->default('pending'); // pending, in_progress, completed, dsb
    $table->text('notes')->nullable();
    $table->foreignId('admin_id')->nullable()->constrained('users');
    $table->foreignId('manager_id')->nullable()->constrained('users');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pm_checks');
    }
};
