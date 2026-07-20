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

public function getFrequencyLabelAttribute()
{
    $activeWeeks = is_array($this->active_weeks) ? $this->active_weeks : json_decode($this->active_weeks, true);
    if (!is_array($activeWeeks)) return 'weekly';

    $count = count($activeWeeks);

    if ($count >= 24 && $count <= 28) return 'bi-weekly';
    if ($count >= 11 && $count <= 14) return 'monthly';
    if ($count >= 3 && $count <= 5) return 'quarterly';
    if ($count >= 1 && $count <= 2) return 'yearly';

    return 'weekly';
}
}