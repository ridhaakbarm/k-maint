<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InternalTicketNote extends Model
{
    protected $fillable = ['internal_ticket_id', 'user_id', 'note'];

    public function ticket()
    {
        return $this->belongsTo(InternalTicket::class, 'internal_ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
