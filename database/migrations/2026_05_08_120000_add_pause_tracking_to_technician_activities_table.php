<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('technician_activities', function (Blueprint $table) {
            $table->timestamp('paused_at')->nullable()->after('end_time');
            $table->timestamp('resumed_at')->nullable()->after('paused_at');
            $table->integer('total_pause_minutes')->default(0)->after('duration');
            $table->unsignedInteger('pause_count')->default(0)->after('total_pause_minutes');
            $table->text('pause_reason')->nullable()->after('pause_count');
            $table->json('pause_resume_log')->nullable()->after('pause_reason');
            $table->foreignId('resumed_from_activity_id')->nullable()->after('pause_resume_log')->constrained('technician_activities')->nullOnDelete();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE technician_activities MODIFY status ENUM('running', 'paused', 'completed') DEFAULT 'running'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE technician_activities MODIFY status ENUM('running', 'completed') DEFAULT 'running'");
        }

        Schema::table('technician_activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('resumed_from_activity_id');
            $table->dropColumn([
                'paused_at',
                'resumed_at',
                'total_pause_minutes',
                'pause_count',
                'pause_reason',
                'pause_resume_log',
            ]);
        });
    }
};
