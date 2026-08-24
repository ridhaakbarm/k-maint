<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;
use App\Models\InternalTicket;
use App\Models\PmCheckItem;
use Illuminate\Support\Facades\Storage;

class MigratePhotosToS3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'photos:migrate-to-s3';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate all local photos (Tickets, Internal Tickets, PM) to AWS S3';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting migration of photos to S3...");

        // Migrate Tickets
        $this->info("Migrating Tickets...");
        $tickets = Ticket::whereNotNull('attachment')->orWhereNotNull('after_photo')->get();
        foreach ($tickets as $ticket) {
            $this->migrateFile($ticket->attachment);
            $this->migrateFile($ticket->after_photo);
        }

        // Migrate Internal Tickets
        $this->info("Migrating Internal Tickets...");
        $internalTickets = InternalTicket::whereNotNull('attachment')->orWhereNotNull('after_photo')->get();
        foreach ($internalTickets as $internalTicket) {
            $this->migrateFile($internalTicket->attachment);
            $this->migrateFile($internalTicket->after_photo);
        }

        // Migrate PM Checks
        $this->info("Migrating PM Check Items...");
        $pmItems = PmCheckItem::whereNotNull('photo_before')->orWhereNotNull('photo_after')->get();
        foreach ($pmItems as $item) {
            $this->migrateFile($item->photo_before);
            $this->migrateFile($item->photo_after);
        }

        $this->info("Migration completed!");
    }

    private function migrateFile($path)
    {
        if (empty($path)) return;

        // Clean up path
        $path = ltrim($path, '/');

        // Check if already in S3
        if (Storage::disk('s3')->exists($path)) {
            $this->info("Already in S3: $path");
            return;
        }

        $fileContent = null;
        
        // Check direct public path first (e.g. public/attachments/...)
        $publicPath = public_path($path);
        if (file_exists($publicPath) && is_file($publicPath)) {
            $fileContent = file_get_contents($publicPath);
        } 
        // Then check Laravel's public disk (storage/app/public/...)
        elseif (Storage::disk('public')->exists($path)) {
            $fileContent = Storage::disk('public')->get($path);
        }

        if ($fileContent !== null) {
            Storage::disk('s3')->put($path, $fileContent, 'public');
            $this->info("Migrated to S3: $path");
        } else {
            $this->warn("Local file not found for migration: $path");
        }
    }
}
