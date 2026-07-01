<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternalTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_no',
        'request_date',
        'requester_id',
        'asset_id',
        'pm_check_item_id',
        'source_type',
        'subject',
        'description',
        'work_result',
        'assigned_to_name',
        'target_date',
        'priority',
        'status',
        'attachment',
        'after_photo',
        'started_at',
        'closed_at',
    ];

    protected $casts = [
        'request_date' => 'datetime',
        'target_date' => 'date',
        'started_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public static function generateTicketNo(): string
    {
        $prefix = 'INT-' . now()->format('ym') . '-';
        $latestTicket = self::where('ticket_no', 'like', $prefix . '%')->latest('id')->first();
        $sequence = $latestTicket ? ((int) substr($latestTicket->ticket_no, -5)) + 1 : 1;

        return $prefix . str_pad($sequence, 5, '0', STR_PAD_LEFT);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function pmCheckItem()
    {
        return $this->belongsTo(PmCheckItem::class);
    }

    public function notes()
    {
        return $this->hasMany(InternalTicketNote::class)->latest();
    }
}
