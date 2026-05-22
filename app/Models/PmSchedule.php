<?php
// app/Models/PmSchedule.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PmSchedule extends Model
{
    use HasFactory;

    // Pastikan menggunakan asset_id
    protected $fillable = ['asset_id', 'schedule_type', 'name', 'description', 'is_active', 'pic_name'];

    public function asset() // Ganti dari machine() ke asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function checklistTemplates()
    {
        return $this->hasMany(ChecklistTemplate::class, 'pm_schedule_id');
    }

    public function pmChecks()
    {
        return $this->hasMany(PmCheck::class);
    }
}