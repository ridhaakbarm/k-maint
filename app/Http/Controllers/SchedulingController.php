<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PmSchedule;
use App\Models\PmCheck;
use App\Models\User;
use App\Models\PmCheckItem;
use Carbon\Carbon; // Pastikan ini ada

class SchedulingController extends Controller
{
    public function index()
{
    $technicians = User::where('role', 'technician')->get();
    $currentWeek = Carbon::now()->weekOfYear;

    // AMBIL DATA PENUGASAN (Terbaru di atas)
    $existingSchedules = PmCheck::with(['pmSchedule.machine', 'technician'])
        ->orderBy('due_date', 'desc')
        ->limit(50) // Ambil 50 data terakhir
        ->get();
    
    return view('scheduling.index', compact('technicians', 'currentWeek', 'existingSchedules'));
}

public function generate(Request $request)
{
    $request->validate([
        'technician_id' => 'required|exists:users,id',
        'due_date' => 'required|date',
        'target_week' => 'required|integer|between:1,52', // Input minggu manual
        'selected_schedules' => 'required|array',
    ]);

    $technician = User::find($request->technician_id);
    // Gunakan target_week dari input, bukan dari due_date lagi
    $weekNumber = $request->target_week; 

    $count = 0;

    foreach ($request->selected_schedules as $scheduleId) {
        $schedule = PmSchedule::with('checklistTemplates')->find($scheduleId);

        // Cek double assignment di minggu & mesin yang sama
        $exists = PmCheck::where('pm_schedule_id', $schedule->id)
                    ->where('week_number', $weekNumber) // Pastikan kolom ini ada di DB
                    ->exists();

        if (!$exists) {
            $pmCheck = PmCheck::create([
                'pm_schedule_id' => $schedule->id,
                'technician_id' => $technician->id,
                'technician_name' => $technician->name,
                'due_date' => $request->due_date,
                'week_number' => $weekNumber, // Simpan info minggu target
                'status' => 'pending',
                'shift' => '-', 
            ]);

            foreach ($schedule->checklistTemplates->where('is_active', true) as $template) {
                // Filter berdasarkan minggu yang dipilih manual tadi
                if (is_array($template->active_weeks) && in_array($weekNumber, $template->active_weeks)) {
                    PmCheckItem::create([
                        'pm_check_id' => $pmCheck->id,
                        'checklist_template_id' => $template->id,
                    ]);
                }
            }
            $count++;
        }
    }

    return back()->with('success', "Berhasil menjadwalkan $count mesin untuk Week $weekNumber.");
}

    /**
     * AJAX: Ambil daftar mesin berdasarkan tipe jadwal
     */
    public function getMachinesBySchedule(Request $request)
    {
        $scheduleType = $request->query('schedule_type');

        // Ambil jadwal + data mesinnya
        $schedules = PmSchedule::with('machine')
                        ->where('schedule_type', $scheduleType)
                        ->where('is_active', true)
                        ->get();

        return response()->json($schedules);
    }
}