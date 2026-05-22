<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoCloseRequestToCloseTickets extends Command
{
    protected $signature = 'tickets:auto-close-requested {--days=2 : Jumlah hari menunggu sebelum auto close}';

    protected $description = 'Auto close ticket request_to_close yang tidak dikonfirmasi user setelah batas hari tertentu.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);
        $closedCount = 0;

        Ticket::with('requester')
            ->where('status', 'request_to_close')
            ->where('updated_at', '<=', $cutoff)
            ->orderBy('id')
            ->chunkById(100, function ($tickets) use (&$closedCount, $days) {
                foreach ($tickets as $ticket) {
                    DB::transaction(function () use ($ticket, $days, &$closedCount) {
                        $note = "Tiket otomatis ditutup oleh system karena sudah {$days} hari berada pada status Request to Close tanpa konfirmasi close dari user.";

                        $ticket->update([
                            'status' => 'closed',
                            'closed_date' => now(),
                            'ga_notes' => trim(($ticket->ga_notes ? $ticket->ga_notes . "\n\n" : '') . $note),
                        ]);

                        $ticket->notes()->create([
                            'user_id' => null,
                            'note' => $note,
                        ]);

                        $closedCount++;
                    });
                }
            });

        $this->info("Auto close selesai. {$closedCount} ticket ditutup.");

        return self::SUCCESS;
    }
}
