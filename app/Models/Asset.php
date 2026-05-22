<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
     use HasFactory;

    protected $fillable = ['fa_code', 'name', 'type', 'equip_tag', 'location'];


   public function tickets()
{
    return $this->hasMany(Ticket::class);
}
public function machineParts()
{
    return $this->hasMany(MachinePart::class, 'asset_id');
}
public function pmSchedules()
{
    // Satu aset bisa punya banyak jadwal PM
    return $this->hasMany(PmSchedule::class, 'asset_id'); 
}
}