<?php
// app/Models/PmCheck.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PmCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'pm_schedule_id', 'technician_id', 'admin_id', 'manager_id',
        'check_date', 'due_date', 'shift', 'status', 'notes',
        'technician_name', 'week_number' // <-- TAMBAHKAN INI
    ];

    protected $casts = [
        'check_date' => 'date',
    ];

    public function pmSchedule()
{
    return $this->belongsTo(PmSchedule::class, 'pm_schedule_id');
}

public function technician()
{
    // Mengacu ke tabel users di K-Maint
    return $this->belongsTo(User::class, 'technician_id');
}

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function checkItems()
    {
        return $this->hasMany(PmCheckItem::class);
    }
}