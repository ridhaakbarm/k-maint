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
    // 1. Update tabel tickets
    DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('open', 'onprogress', 'schedule', 'request_to_close', 'rejected', 'closed', 'pending') DEFAULT 'open'");

    // 2. Update tabel ticket_status_histories (PENTING kawan!)
    DB::statement("ALTER TABLE ticket_status_histories MODIFY COLUMN old_status ENUM('open', 'onprogress', 'schedule', 'request_to_close', 'rejected', 'closed', 'pending') NULL");
    DB::statement("ALTER TABLE ticket_status_histories MODIFY COLUMN new_status ENUM('open', 'onprogress', 'schedule', 'request_to_close', 'rejected', 'closed', 'pending') NOT NULL");
}

public function down()
{
    // Kembalikan ke asal jika diperlukan
    DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('open', 'onprogress', 'schedule', 'request_to_close', 'rejected', 'closed') DEFAULT 'open'");
    DB::statement("ALTER TABLE ticket_status_histories MODIFY COLUMN old_status ENUM('open', 'onprogress', 'schedule', 'request_to_close', 'rejected', 'closed') NULL");
    DB::statement("ALTER TABLE ticket_status_histories MODIFY COLUMN new_status ENUM('open', 'onprogress', 'schedule', 'request_to_close', 'rejected', 'closed') NOT NULL");
}
};
