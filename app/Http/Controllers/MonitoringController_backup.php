<?php

namespace App\Http\Controllers;

use App\Models\TechnicianActivity;
use App\Models\Ticket;
use App\Models\PmCheck;
use App\Models\PmCheckItem;
use App\Models\PmSchedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class MonitoringController extends Controller
{
    // DASHBOARD ADMIN: Monitoring Real-time & Grafik
public function index(Request $request)
    {
        $user = Auth::user();
        $today = now()->toDateString();
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', 'all');
        $currentWeek = now()->weekOfYear;

        // 1. STATISTIK TIKET
        $ticketQuery = Ticket::query();
        if ($year != 'all') { $ticketQuery->whereYear('request_date', $year); }
        if ($month != 'all') { $ticketQuery->whereMonth('request_date', $month); }
        
        $ticketStats = [
            'open' => $ticketQuery->clone()->where('status', 'open')->count(),
            'onprogress' => $ticketQuery->clone()->where('status', 'onprogress')->count(),
            'closed' => $ticketQuery->clone()->where('status', 'closed')->count(),
        ];

        // 2. STATISTIK PM
        $weeklyPmSchedules = PmSchedule::where('is_active', true)
            ->whereHas('checklistTemplates', function($q) use ($currentWeek) {
                $q->where('is_active', true)
                  ->where(function($query) use ($currentWeek) {
                      $query->whereJsonContains('active_weeks', $currentWeek)
                            ->orWhereJsonContains('active_weeks', (string)$currentWeek);
                  });
            })->get();

        $pmStats = [
            'machines_total' => $weeklyPmSchedules->count(),
            'machines_done' => PmCheck::where('week_number', $currentWeek)
                                ->whereIn('status', ['completed', 'verified', 'approved'])
                                ->count(),
            'items_done' => PmCheckItem::whereHas('pmCheck', function($q) use ($currentWeek) {
                                $q->where('week_number', $currentWeek);
                            })->whereNotNull('condition')->count(),
        ];

        // 3. LOGIKA WORKLOAD (FIX ERROR: Undefined Variable $availableMinutes)
        $mtcUsers = User::where('role', 'mtc')->get();
        $technicianStats = [];

        foreach ($mtcUsers as $mtc) {
            // Cari attendance hari ini ATAU attendance kemarin yang belum clock-out (untuk shift 3 cross-day)
            $attendance = \App\Models\TechnicianAttendance::where('user_id', $mtc->id)
                ->where(function($query) use ($today) {
                    $query->where('date', $today)
                          ->orWhere(function($q) use ($today) {
                              // Untuk shift 3, cek kemarin jika belum clock-out
                              $q->where('date', \Carbon\Carbon::yesterday()->toDateString())
                                ->whereNull('clock_out');
                          });
                })
                ->orderBy('created_at', 'desc')
                ->first();

            $activities = TechnicianActivity::where('user_id', $mtc->id)
                ->whereDate('start_time', $today)->get();

            // REVISI: Hitung durasi aktivitas secara "Live"
            $processedActivities = $activities->map(function($act) {
                if ($act->status == 'running') {
                    $act->total_duration = $act->start_time->diffInMinutes(now());
                } else {
                    $act->total_duration = $act->duration;
                }
                return $act;
            });

            $totalWorkMinutes = $processedActivities->sum('total_duration');

            // --- DEFINISIKAN VARIABLE YANG HILANG DI SINI ---
            $availableMinutes = 0;
            if ($attendance && $attendance->clock_in) {
                $endTime = $attendance->clock_out ?: now();
                $availableMinutes = $attendance->clock_in->diffInMinutes($endTime);
            }

            // Group data untuk Pie Chart
            $groupedData = $processedActivities->groupBy('category')->map(fn($row) => $row->sum('total_duration'));

            $technicianStats[] = [
                'user' => $mtc,
                'attendance' => $attendance,
                'activities' => $activities,
                'workload_pct' => $availableMinutes > 0 ? round(($totalWorkMinutes / $availableMinutes) * 100, 2) : 0,
                'chart_labels' => $groupedData->keys(),
                'chart_data' => $groupedData->values(),
                'total_work_minutes' => $totalWorkMinutes
            ];
        }

        // 4. DATA GLOBAL
        $availableYears = Ticket::selectRaw('YEAR(request_date) as year')->distinct()->orderBy('year', 'desc')->pluck('year');
        if ($availableYears->isEmpty()) { $availableYears = collect([date('Y')]); }

        $activeActivities = TechnicianActivity::with(['user', 'ticket.asset', 'pmCheck.pmSchedule.asset'])
            ->where('status', 'running')->get();

        $chartData = TechnicianActivity::whereDate('start_time', Carbon::today())
            ->where('status', 'completed')
            ->selectRaw('category, SUM(duration) as total_minutes')
            ->groupBy('category')->get();

        $openTickets = Ticket::with(['asset', 'requester'])->where('status', 'open')->latest()->limit(5)->get();

        return view('dashboard', compact(
            'ticketStats', 'pmStats', 'technicianStats', 'activeActivities', 
            'chartData', 'year', 'availableYears', 'openTickets', 
            'weeklyPmSchedules', 'currentWeek'
        ));
    }

    // Fungsi Clock In / Out untuk Teknisi
public function clockIn(Request $request)
{
    \App\Models\TechnicianAttendance::updateOrCreate(
        ['user_id' => Auth::id(), 'date' => now()->toDateString()],
        [
            'clock_in' => now(),
            'shift' => $request->shift
        ]
    );
    return back()->with('success', 'Berhasil Clock In! Selamat bekerja kawan.');
}

public function clockOut()
{
    // Cari attendance yang belum clock-out, prioritaskan hari ini, kemarin (untuk shift 3 cross-day)
    $attendance = \App\Models\TechnicianAttendance::where('user_id', Auth::id())
        ->whereNull('clock_out')
        ->where(function($query) {
            $query->where('date', now()->toDateString())
                  ->orWhere('date', now()->subDay()->toDateString());
        })
        ->orderBy('created_at', 'desc')
        ->first();

    if ($attendance) {
        $attendance->update(['clock_out' => now()]);

        // Opsional: Tutup semua aktivitas yang masih running saat pulang
        TechnicianActivity::where('user_id', Auth::id())
            ->where('status', 'running')
            ->update([
                'status' => 'completed',
                'end_time' => now(),
                'duration' => \DB::raw('TIMESTAMPDIFF(MINUTE, start_time, NOW())')
            ]);
    } else {
        return back()->with('error', 'Tidak ditemukan data Clock In yang aktif. Silakan Clock In terlebih dahulu.');
    }

    return back()->with('success', 'Berhasil Clock Out! Terima kasih atas kerja kerasnya hari ini.');
}

    // DASHBOARD TEKNISI: Tempat input aktivitas harian
    public function technicianDashboard()
    {
        $currentActivity = TechnicianActivity::where('user_id', Auth::id())
            ->where('status', 'running')
            ->first();

        return view('teknisi.dashboard', compact('currentActivity'));
    }

    // ACTION: Mulai Aktivitas (PM, Breakdown, atau Lain-lain)
    public function startActivity(Request $request)
    {
        $user = Auth::user();

        // 1. Tutup aktivitas yang sedang berjalan (jika ada)
        $running = TechnicianActivity::where('user_id', $user->id)
            ->where('status', 'running')
            ->first();

        if ($running) {
            $endTime = now();
            $duration = $running->start_time->diffInMinutes($endTime);
            $running->update([
                'status' => 'completed',
                'end_time' => $endTime,
                'duration' => $duration
            ]);
        }

        // 2. Buat aktivitas baru
        $description = $request->description;
        if ($request->description === 'OTHER_VAL') {
            $description = $request->other_desc;
        }

        TechnicianActivity::create([
            'user_id' => $user->id,
            'category' => $request->category,
            'description' => $description,
            'reference_id' => $request->reference_id,
            'start_time' => now(),
            'status' => 'running'
        ]);

        return back()->with('success', 'Aktivitas kerja dimulai!');
    }

    // ACTION: Berhenti / Selesai Kerja
    public function stopActivity($id)
    {
        $activity = TechnicianActivity::findOrFail($id);
        $endTime = now();
        $duration = $activity->start_time->diffInMinutes($endTime);

        $activity->update([
            'status' => 'completed',
            'end_time' => $endTime,
            'duration' => $duration
        ]);

        return back()->with('success', 'Aktivitas selesai. Durasi: ' . $duration . ' menit.');
    }

    // MONITORING TIM TEKNISI (Admin Only)
    public function teamMonitoring(Request $request)
    {
        // Pastikan hanya admin yang bisa akses
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        // Filter parameters - PASTIKAN DEFAULT BENAR
        $period = $request->get('period', 'daily');
        $technicianId = $request->input('technician', 'all'); // Gunakan input() bukan get()

        // Jika technicianId kosong atau null, set ke 'all'
        if (empty($technicianId) || is_null($technicianId)) {
            $technicianId = 'all';
        }

        $dateFrom = $request->get('date_from', now()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());

        // Set date range based on period
        switch ($period) {
            case 'weekly':
                $dateFrom = now()->startOfWeek()->toDateString();
                $dateTo = now()->endOfWeek()->toDateString();
                break;
            case 'monthly':
                $dateFrom = now()->startOfMonth()->toDateString();
                $dateTo = now()->endOfMonth()->toDateString();
                break;
            default: // daily
                $dateFrom = $request->get('date_from', now()->toDateString());
                $dateTo = $request->get('date_to', now()->toDateString());
        }

        // Query activities
        $query = TechnicianActivity::with(['user', 'ticket.asset', 'pmCheck.pmSchedule.asset'])
            ->whereDate('start_time', '>=', $dateFrom)
            ->whereDate('start_time', '<=', $dateTo)
            ->orderBy('start_time', 'desc');

        // Filter by technician - Perbaikan: gunakan in_array()
        $excludedValues = ['all', '', null];
        if (!in_array($technicianId, $excludedValues, true)) {
            $query->where('user_id', $technicianId);
        }

        $activities = $query->get();

        // Get all technicians for filter
        $technicians = User::where('role', 'mtc')->orderBy('name')->get();

        // Calculate summary per technician
        $technicianSummary = collect();
        foreach ($technicians as $tech) {
            $techActivities = $activities->where('user_id', $tech->id);

            // Calculate total duration (handle running activities)
            $totalMinutes = $techActivities->sum(function($act) {
                if ($act->status === 'running') {
                    return $act->start_time->diffInMinutes(now());
                }
                return $act->duration ?? 0;
            });

            // Group by category
            $byCategory = $techActivities->groupBy('category')->map(function($items) {
                return $items->sum(function($act) {
                    if ($act->status === 'running') {
                        return $act->start_time->diffInMinutes(now());
                    }
                    return $act->duration ?? 0;
                });
            });

            $technicianSummary->push([
                'user' => $tech,
                'total_activities' => $techActivities->count(),
                'total_minutes' => $totalMinutes,
                'total_hours' => round($totalMinutes / 60, 2),
                'by_category' => $byCategory,
                'pm_count' => $techActivities->where('category', 'PM')->count(),
                'breakdown_count' => $techActivities->where('category', 'Breakdown')->count(),
                'other_count' => $techActivities->where('category', 'Lain-lain')->count(),
            ]);
        }

        // Overall summary
        $overallSummary = [
            'total_activities' => $activities->count(),
            'total_minutes' => $activities->sum(function($act) {
                if ($act->status === 'running') {
                    return $act->start_time->diffInMinutes(now());
                }
                return $act->duration ?? 0;
            }),
            'by_category' => $activities->groupBy('category')->map(function($items) {
                return $items->sum(function($act) {
                    if ($act->status === 'running') {
                        return $act->start_time->diffInMinutes(now());
                    }
                    return $act->duration ?? 0;
                });
            }),
        ];
        $overallSummary['total_hours'] = round($overallSummary['total_minutes'] / 60, 2);

        // Chart data for productivity
        $chartLabels = $technicianSummary->pluck('user.name');
        $chartData = $technicianSummary->pluck('total_hours');

        // ========== PM PROGRESS DATA ==========
        $currentWeek = now()->weekOfYear;

        // 1. Progress per Week
        $pmProgressByWeek = collect();
        $weeksToShow = [$currentWeek]; // Default hanya week yang sedang berjalan

        // Ambil 4 week terakhir untuk display
        for ($i = 3; $i >= 0; $i--) {
            $weeksToShow[] = $currentWeek - $i;
        }
        $weeksToShow = array_unique(array_filter(array_values($weeksToShow), function($w) {
            return $w > 0 && $w <= 52;
        }));
        sort($weeksToShow);

        foreach ($weeksToShow as $week) {
            // Hitung total item checklist yang harus dikerjakan untuk week ini
            $schedules = PmSchedule::where('is_active', true)
                ->where('schedule_type', 'weekly')
                ->whereHas('checklistTemplates', function($q) use ($week) {
                    $q->where('is_active', true)
                      ->where(function($query) use ($week) {
                          $query->whereJsonContains('active_weeks', $week)
                                ->orWhereJsonContains('active_weeks', (string)$week);
                      });
                })->with(['checklistTemplates' => function($q) use ($week) {
                    $q->where('is_active', true);
                }])->get();

            $totalItems = 0;
            foreach ($schedules as $schedule) {
                $activeWeeksTemplates = $schedule->checklistTemplates->filter(function($template) use ($week) {
                    $activeWeeks = is_array($template->active_weeks) ? $template->active_weeks : json_decode($template->active_weeks, true);
                    return is_array($activeWeeks) && in_array($week, $activeWeeks);
                });
                $totalItems += $activeWeeksTemplates->count();
            }

            // Hitung item yang sudah selesai (completed/verified status)
            $completedItems = PmCheckItem::whereHas('pmCheck', function($q) use ($week) {
                $q->where('week_number', $week)
                  ->whereIn('status', ['completed', 'verified', 'approved']);
            })->whereNotNull('condition')->count();

            // Hitung item yang sedang in_progress (sudah diisi tapi belum selesai)
            $inProgressItems = PmCheckItem::whereHas('pmCheck', function($q) use ($week) {
                $q->where('week_number', $week)->where('status', 'in_progress');
            })->whereNotNull('condition')->count();

            $percentage = $totalItems > 0 ? round(($completedItems / $totalItems) * 100, 1) : 0;

            $pmProgressByWeek->push([
                'week' => $week,
                'total_items' => $totalItems,
                'completed_items' => $completedItems,
                'in_progress_items' => $inProgressItems,
                'remaining_items' => $totalItems - $completedItems,
                'percentage' => $percentage,
                'is_current_week' => $week == $currentWeek
            ]);
        }

        // 2. Progress per PIC/Technician
        $pmProgressByPic = collect();

        // --- PERBAIKAN DI SINI ---
$pmChecksQuery = PmCheck::with(['pmSchedule.asset', 'checkItems', 'technician'])
    ->where('week_number', $currentWeek);

// Tambahkan filter ini supaya tabel kanan ikut berubah! - Perbaiki kondisi
$excludedValuesPm = ['all', '', null];
if (!in_array($technicianId, $excludedValuesPm, true)) {
    $pmChecksQuery->where('technician_id', $technicianId);
}

$pmChecks = $pmChecksQuery->get();
// -------------------------

// Group by technician
$pmChecksByTech = $pmChecks->groupBy('technician_id');

        foreach ($pmChecksByTech as $picId => $checks) {
            $technician = User::find($picId);
            if (!$technician) continue;

            $totalItems = 0;
            $completedItems = 0;
            $inProgressItems = 0;
            $machines = [];

            foreach ($checks as $check) {
                // Hitung total items untuk PM check ini
                $checkItems = $check->checkItems;
                $totalItems += $checkItems->count();

                // Hitung yang sudah selesai (ada condition)
                $itemsWithCondition = $checkItems->whereNotNull('condition')->count();
                $completedItems += $itemsWithCondition;

                // Cek status PM
                if (in_array($check->status, ['completed', 'verified', 'approved'])) {
                    $inProgressItems += $itemsWithCondition;
                }

                // Collect machines
                if ($check->pmSchedule && $check->pmSchedule->asset) {
                    $machines[] = [
                        'name' => $check->pmSchedule->asset->name,
                        'status' => $check->status,
                        'progress' => $checkItems->count() > 0
                            ? round(($itemsWithCondition / $checkItems->count()) * 100, 1)
                            : 0
                    ];
                }
            }

            $percentage = $totalItems > 0 ? round(($completedItems / $totalItems) * 100, 1) : 0;

            $pmProgressByPic->push([
                'technician' => $technician,
                'total_items' => $totalItems,
                'completed_items' => $completedItems,
                'remaining_items' => $totalItems - $completedItems,
                'percentage' => $percentage,
                'machines' => $machines,
                'total_machines' => count($machines)
            ]);
        }

        // Sort by percentage descending
        $pmProgressByPic = $pmProgressByPic->sortByDesc('percentage')->values();

        return view('monitoring.team', compact(
            'activities', 'technicians', 'technicianSummary', 'overallSummary',
            'period', 'technicianId', 'dateFrom', 'dateTo',
            'chartLabels', 'chartData',
            'pmProgressByWeek', 'pmProgressByPic', 'currentWeek'
        ));
    }

    // EXPORT TEAM MONITORING TO EXCEL
    public function exportTeamMonitoring(Request $request)
    {
        // Pastikan hanya admin yang bisa akses
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        // Filter parameters (sama seperti teamMonitoring)
        $period = $request->get('period', 'daily');
        $technicianId = $request->get('technician', 'all'); // Default 'all' jika tidak ada
        $dateFrom = $request->get('date_from', now()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());

        // Set date range based on period
        switch ($period) {
            case 'weekly':
                $dateFrom = now()->startOfWeek()->toDateString();
                $dateTo = now()->endOfWeek()->toDateString();
                break;
            case 'monthly':
                $dateFrom = now()->startOfMonth()->toDateString();
                $dateTo = now()->endOfMonth()->toDateString();
                break;
            default: // daily
                $dateFrom = $request->get('date_from', now()->toDateString());
                $dateTo = $request->get('date_to', now()->toDateString());
        }

        // Query activities (sama seperti teamMonitoring)
        $query = TechnicianActivity::with(['user', 'ticket.asset', 'pmCheck.pmSchedule.asset'])
            ->whereDate('start_time', '>=', $dateFrom)
            ->whereDate('start_time', '<=', $dateTo)
            ->orderBy('start_time', 'desc');

        // Filter by technician - Perbaikan: gunakan in_array()
        $excludedValues = ['all', '', null];
        if (!in_array($technicianId, $excludedValues, true)) {
            $query->where('user_id', $technicianId);
        }

        $activities = $query->get();

        // Get all technicians
        $technicians = User::where('role', 'mtc')->orderBy('name')->get();

        // Calculate summary per technician (sama seperti teamMonitoring)
        $technicianSummary = collect();
        foreach ($technicians as $tech) {
            $techActivities = $activities->where('user_id', $tech->id);

            $totalMinutes = $techActivities->sum(function($act) {
                if ($act->status === 'running') {
                    return $act->start_time->diffInMinutes(now());
                }
                return $act->duration ?? 0;
            });

            $byCategory = $techActivities->groupBy('category')->map(function($items) {
                return $items->sum(function($act) {
                    if ($act->status === 'running') {
                        return $act->start_time->diffInMinutes(now());
                    }
                    return $act->duration ?? 0;
                });
            });

            $technicianSummary->push([
                'user' => $tech,
                'total_activities' => $techActivities->count(),
                'total_minutes' => $totalMinutes,
                'total_hours' => round($totalMinutes / 60, 2),
                'by_category' => $byCategory,
                'pm_count' => $techActivities->where('category', 'PM')->count(),
                'breakdown_count' => $techActivities->where('category', 'Breakdown')->count(),
                'other_count' => $techActivities->where('category', 'Lain-lain')->count(),
            ]);
        }

        // Overall summary
        $overallSummary = [
            'total_activities' => $activities->count(),
            'total_minutes' => $activities->sum(function($act) {
                if ($act->status === 'running') {
                    return $act->start_time->diffInMinutes(now());
                }
                return $act->duration ?? 0;
            }),
            'by_category' => $activities->groupBy('category')->map(function($items) {
                return $items->sum(function($act) {
                    if ($act->status === 'running') {
                        return $act->start_time->diffInMinutes(now());
                    }
                    return $act->duration ?? 0;
                });
            }),
        ];
        $overallSummary['total_hours'] = round($overallSummary['total_minutes'] / 60, 2);

        // Generate filename
        $filename = 'Monitoring_Tim_Teknisi_' . $period . '_' . str_replace('/', '_', $dateFrom) . '_sd_' . str_replace('/', '_', $dateTo) . '.xlsx';

        // Export using Excel
        $export = new \App\Exports\TechnicianActivityExport(
            $activities,
            $technicians,
            $technicianSummary,
            $overallSummary,
            $period,
            $dateFrom,
            $dateTo,
            $technicianId
        );

        return \Maatwebsite\Excel\Facades\Excel::download($export, $filename);
    }
}