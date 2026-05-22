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
    // Kita gunakan DB raw agar lebih aman merubah ENUM di MySQL kawan
    DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('open', 'onprogress', 'schedule', 'request_to_close', 'rejected', 'closed', 'pending') DEFAULT 'open'");
}

public function down()
{
    // Jika di-rollback, kita kembalikan ke daftar lama
    DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('open', 'onprogress', 'schedule', 'request_to_close', 'rejected', 'closed') DEFAULT 'open'");
}
};
