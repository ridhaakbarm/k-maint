<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PmCheckItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'pm_check_id',
        'checklist_template_id',
        'condition',
        'action_taken',
        'next_action',
        'follow_up_status',
        'checked_at',
        'checked_by_user_id', // Pastikan kolom ini ada di fillable
        'photo_before',
        'follow_up_status',
        'photo_after',
        'verified_by_user_id',
        'leader_signature',
        // TAMBAHKAN DI SINI KAWAN
    'follow_up_note',
    'execution_date',
    'executed_by',
    'verified_by',
    'approved_by',
    'remark',
    ];

    /**
     * PENTING: Mengubah string menjadi objek Carbon
     */
    protected $casts = [
        'checked_at' => 'datetime',
    ];

    public function checklistTemplate()
    {
        return $this->belongsTo(ChecklistTemplate::class);
    }

    public function pmCheck()
    {
        return $this->belongsTo(PmCheck::class, 'pm_check_id');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    /**
     * Relasi ke teknisi yang melakukan pengecekan per item
     */
    public function checkedBy()
    {
        return $this->belongsTo(User::class, 'checked_by_user_id');
    }
}