<?php

namespace Database\Seeders;

use App\Models\TechnicianActivity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixActivityDurationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Memulai perbaikan data durasi aktivitas...');

        // Hitung dan update semua aktivitas yang statusnya completed tapi duration masih 0
        $activities = TechnicianActivity::where('status', 'completed')
            ->whereNotNull('end_time')
            ->where(function($q) {
                $q->where('duration', 0)
                  ->orWhereNull('duration');
            })
            ->get();

        $count = 0;
        foreach ($activities as $activity) {
            $duration = $activity->start_time->diffInMinutes($activity->end_time);
            $activity->duration = $duration;
            $activity->save();
            $count++;
        }

        $this->command->info("Selesai! {$count} aktivitas berhasil diperbarui.");
    }
}
