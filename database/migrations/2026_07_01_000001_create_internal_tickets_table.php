<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_no')->unique();
            $table->dateTime('request_date');
            $table->foreignId('requester_id')->constrained('users');
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->foreignId('pm_check_item_id')->nullable()->constrained('pm_check_items')->nullOnDelete();
            $table->string('source_type')->default('lisan');
            $table->string('subject');
            $table->text('description');
            $table->text('work_result')->nullable();
            $table->string('assigned_to_name')->nullable();
            $table->date('target_date')->nullable();
            $table->string('priority')->default('normal');
            $table->enum('status', ['open', 'onprogress', 'pending', 'closed'])->default('open');
            $table->string('attachment')->nullable();
            $table->string('after_photo')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_tickets');
    }
};
