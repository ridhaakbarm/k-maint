<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketStatusHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'old_status',
        'new_status',
        'notes'
    ];

    protected $casts = [
        'old_status' => 'string',
        'new_status' => 'string'
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusBadgeClass($status)
    {
        switch ($status) {
            case 'open':
                return 'bg-primary';
            case 'onprogress':
                return 'bg-warning';
            case 'request_to_close':
                return 'bg-info';
            case 'closed':
                return 'bg-success';
            default:
                return 'bg-secondary';
        }
    }

    public function getActionDescription()
    {
        $userName = $this->user->name;
        $oldStatus = $this->old_status ? ucfirst($this->old_status) : 'New';
        $newStatus = ucfirst($this->new_status);

        if ($this->old_status === null) {
            return "Ticket dibuat oleh {$userName}";
        }

        $actions = [
            'open_to_onprogress' => "Diproses oleh Teknisi ({$userName})",
            'onprogress_to_request_to_close' => "Diselesaikan oleh Teknisi ({$userName})",
            'request_to_close_to_closed' => "Disetujui oleh User ({$userName})",
            'request_to_close_to_onprogress' => "Ditolak oleh User ({$userName})",
            'onprogress_to_closed' => "Ditutup oleh Teknisi ({$userName})",
        ];

        $key = $this->old_status . '_to_' . $this->new_status;
        
        return $actions[$key] ?? "Status diubah dari {$oldStatus} ke {$newStatus} oleh {$userName}";
    }
}