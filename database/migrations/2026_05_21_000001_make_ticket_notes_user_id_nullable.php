<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE ticket_notes DROP FOREIGN KEY ticket_notes_user_id_foreign');
        DB::statement('ALTER TABLE ticket_notes MODIFY user_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE ticket_notes ADD CONSTRAINT ticket_notes_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL');
    }

    public function down(): void
    {
        DB::statement('UPDATE ticket_notes SET user_id = (SELECT id FROM users ORDER BY id ASC LIMIT 1) WHERE user_id IS NULL');
        DB::statement('ALTER TABLE ticket_notes DROP FOREIGN KEY ticket_notes_user_id_foreign');
        DB::statement('ALTER TABLE ticket_notes MODIFY user_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE ticket_notes ADD CONSTRAINT ticket_notes_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id)');
    }
};
