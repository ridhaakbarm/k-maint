<?php
// app/Models/ChecklistTemplate.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChecklistTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'pm_schedule_id', 'item_name', 'checked_part', 'operation_source',
        'instructions', 'check_standard', 'order', 'is_active', 'active_weeks'

    ];

    protected $casts = [
        'active_weeks' => 'array',
        'is_active' => 'boolean',
    ];
    
    public function pmSchedule()
{
    return $this->belongsTo(PmSchedule::class, 'pm_schedule_id');
}

public function checkItems()
{
    return $this->hasMany(PmCheckItem::class, 'checklist_template_id');
}
}