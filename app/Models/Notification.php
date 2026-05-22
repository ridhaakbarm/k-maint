<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ticket_id',
        'type',
        'message',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function markAsRead()
    {
        $this->update(['is_read' => true]);
    }

    // Scope untuk notifikasi yang belum dibaca
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    // Method untuk membuat notifikasi baru
    public static function createNewTicketNotification($ticket)
    {
        // Dapatkan semua user admin dan GA
        $users = User::whereIn('role', ['admin', 'ga','mtc'])->get();
        
        foreach ($users as $user) {
            self::create([
                'user_id' => $user->id,
                'ticket_id' => $ticket->id,
                'type' => 'new_ticket',
                'message' => "Ticket baru: {$ticket->subject} dari {$ticket->requester->name}",
            ]);
        }
    }

    // Method untuk membuat notifikasi update status
    public static function createStatusUpdateNotification($ticket, $oldStatus, $newStatus)
    {
        // Pastikan user exists
        $user = User::find($ticket->requester_id);
        if (!$user) {
            return;
        }

        self::create([
            'user_id' => $ticket->requester_id,
            'ticket_id' => $ticket->id,
            'type' => 'status_update',
            'message' => "Status ticket {$ticket->ticket_no} berubah dari {$oldStatus} menjadi {$newStatus}",
        ]);
    }
}