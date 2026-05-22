<?php

namespace App\Console\Commands;

use App\Models\PmSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanScheduleNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pm:clean-names';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Membersihkan nama jadwal PM dari prefix "FA - " yang sudah tersimpan di database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memperbaiki nama jadwal PM dengan nama mesin yang benar...');
        $this->line('=================================');

        // Ambil semua jadwal dan relasi asset-nya
        $schedules = PmSchedule::with('asset')->get();

        if ($schedules->isEmpty()) {
            $this->warn('Tidak ada jadwal ditemukan.');
            return Command::SUCCESS;
        }

        // Filter jadwal yang namanya tidak sesuai (mengandung FA- atau tidak ada nama mesin)
        $needUpdate = $schedules->filter(function ($schedule) {
            // Cek jika nama tidak mengandung nama mesin yang seharusnya
            $expectedName = " - {$schedule->asset->name}";
            return strpos($schedule->name, $expectedName) === false;
        });

        if ($needUpdate->isEmpty()) {
            $this->warn('Semua nama jadwal sudah benar.');
            return Command::SUCCESS;
        }

        $this->info("Ditemukan {$needUpdate->count()} jadwal yang perlu diperbarui.");
        $this->newLine();

        // Tentukan tipe jadwal
        $typeMap = [
            'weekly' => 'Jadwal Rutin (Weekly)',
            'yearly' => 'Jadwal Major (Yearly)'
        ];

        // Tampilkan daftar yang akan diupdate
        $this->table(
            ['ID', 'Nama Lama', 'Nama Baru'],
            $needUpdate->take(10)->map(function ($schedule) use ($typeMap) {
                $typeName = $typeMap[$schedule->schedule_type] ?? 'Unknown';
                $newName = "{$typeName} - {$schedule->asset->name}";

                return [
                    $schedule->id,
                    $schedule->name,
                    $newName
                ];
            })
        );

        if ($needUpdate->count() > 10) {
            $this->line("... dan " . ($needUpdate->count() - 10) . " lainnya");
        }

        if (!$this->confirm('Lanjutkan dengan update data?', true)) {
            $this->warn('Dibatalkan.');
            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('Memperbarui data...');

        $bar = $this->output->createProgressBar($needUpdate->count());
        $bar->start();

        $updated = 0;
        foreach ($needUpdate as $schedule) {
            $typeName = $typeMap[$schedule->schedule_type] ?? 'Unknown';
            $newName = "{$typeName} - {$schedule->asset->name}";

            // Update nama hanya jika berubah
            if ($schedule->name !== $newName) {
                $schedule->update(['name' => $newName]);
                $updated++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->newLine();

        $this->info("✅ Berhasil! {$updated} jadwal telah diperbarui.");
        $this->line('=================================');

        return Command::SUCCESS;
    }
}
