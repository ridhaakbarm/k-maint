<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachinePart extends Model
{
    use HasFactory;

    // Field yang boleh diisi secara massal
    protected $fillable = ['asset_id', 'name'];

    /**
     * Relasi: Satu bagian mesin dimiliki oleh satu Asset (Mesin)
     */
    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }
}