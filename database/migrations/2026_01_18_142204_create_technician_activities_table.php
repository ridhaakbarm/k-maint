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
    Schema::create('technician_activities', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Teknisi
        $table->enum('category', ['PM', 'Breakdown', 'Lain-lain']);
        $table->string('description')->nullable(); // Untuk aktivitas manual
        $table->unsignedBigInteger('reference_id')->nullable(); // ID Tiket atau ID PM Check
        $table->timestamp('start_time');
        $table->timestamp('end_time')->nullable();
        $table->integer('duration')->default(0); // Durasi dalam menit
        $table->enum('status', ['running', 'completed'])->default('running');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technician_activities');
    }
};
