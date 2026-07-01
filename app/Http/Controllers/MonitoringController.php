<?php

namespace App\Http\Controllers;

use App\Models\TechnicianActivity;
use App\Models\Ticket;
use App\Models\PmCheck;
use App\Models\PmCheckItem;
use App\Models\PmSchedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class MonitoringController extends Controller
{
    // DASHBOARD ADMIN: Monitoring Real-time & Grafik    public function index(Request $request)
    public function index(Request $request)
    {
        $user = Auth::user();
        $today = now()->toDateString();
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', 'all');
        $currentWeek = now()->weekOfYear;
        $currentYear = now()->year;

        // 1. STATISTIK TIKET
        $ticketQuery = Ticket::query();
        if ($year != 'all') {
            $ticketQuery->whereYear('request_date', $year);
        }
        if ($month != 'all') {
            $ticketQuery->whereMonth('request_date', $month);
        }

        $ticketStats = [
            'open' => $ticketQuery->clone()->where('status', 'open')->count(),
            'onprogress' => $ticketQuery->clone()->where('status', 'onprogress')->count(),
            'closed' => $ticketQuery->clone()->where('status', 'closed')->count(),
        ];

        // 2. STATISTIK PM
        $weeklyPmSchedules = PmSchedule::with(['checklistTemplates' => function ($q) use ($currentWeek) {
            $q->where('is_active', true)
                ->where(function ($query) use ($currentWeek) {
                    $query->whereJsonContains('active_weeks', $currentWeek)
                        ->orWhereJsonContains('active_weeks', (string)$currentWeek);
                });
        }])
            ->where('is_active', true)
            ->where('schedule_type', 'weekly')
            ->whereHas('checklistTemplates', function ($q) use ($currentWeek) {
                $q->where('is_active', true)
                    ->where(function ($query) use ($currentWeek) {
                        $query->whereJsonContains('active_weeks', $currentWeek)
                            ->orWhereJsonContains('active_weeks', (string)$currentWeek);
                    });
            })->get();

        $weeklyPmScheduleIds = $weeklyPmSchedules->pluck('id');
        $pmTotalItems = $weeklyPmSchedules->sum(fn($schedule) => $schedule->checklistTemplates->count());
        $completedPmStatuses = ['completed', 'verified', 'approved'];
        $pmDoneItems = PmCheckItem::whereHas('pmCheck', function ($q) use ($currentWeek, $currentYear, $weeklyPmScheduleIds) {
            $q->where('week_number', $currentWeek)
                ->whereYear('check_date', $currentYear)
                ->whereIn('pm_schedule_id', $weeklyPmScheduleIds);
        })
            ->whereNotNull('condition')
            ->where('condition', '!=', '')
            ->count();

        $pmStats = [
            'machines_total' => $weeklyPmSchedules->count(),
            'machines_done' => PmCheck::where('week_number', $currentWeek)
                ->whereYear('check_date', $currentYear)
                ->whereIn('pm_schedule_id', $weeklyPmScheduleIds)
                ->whereIn('status', $completedPmStatuses)
                ->count(),
            'items_total' => $pmTotalItems,
            'items_done' => $pmDoneItems,
            'completion_percentage' => $pmTotalItems > 0 ? round(($pmDoneItems / $pmTotalItems) * 100, 1) : 0,
        ];

        // 3. LOGIKA WORKLOAD (FIX ERROR: Undefined Variable $availableMinutes)
        $mtcUsers = User::where('role', 'mtc')->get();
        $technicianStats = [];

        foreach ($mtcUsers as $mtc) {
            // Cari attendance hari ini ATAU attendance kemarin yang belum clock-out (untuk shift 3 cross-day)
            $attendance = \App\Models\TechnicianAttendance::where('user_id', $mtc->id)
                ->where(function ($query) use ($today) {
                $query->where('date', $today)
                    ->orWhere(function ($q) use ($today) {
                    // Untuk shift 3, cek kemarin jika belum clock-out
                    $q->where('date', \Carbon\Carbon::yesterday()->toDateString())
                        ->whereNull('clock_out');
                }
                );
            })
                ->orderBy('created_at', 'desc')
                ->first();

            $activities = TechnicianActivity::where('user_id', $mtc->id)
                ->whereDate('start_time', $today)->get();

            // REVISI: Hitung durasi aktivitas secara "Live"
            $processedActivities = $activities->map(function ($act) {
                if ($act->status == 'running') {
                    $act->total_duration = $act->start_time->diffInMinutes(now());
                }
                else {
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
        if ($availableYears->isEmpty()) {
            $availableYears = collect([date('Y')]);
        }

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

    // Fungsi Clock In / Out untuk Teknisi    public function clockIn(Request $request)    {
    public function clockIn(Request $request) // <--- 2. BARIS INI KEMUNGKINAN TERHAPUS, TAMBAHKAN LAGI!

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
            ->where(function ($query) {
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
                ->get()
                ->each(fn($activity) => $activity->complete(now()));
        }
        else {
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

    public function pmMonitoring(Request $request)
    {
        $user = Auth::user();
        if (!($user->isAdmin() || $user->isManager() || $user->isSPV() || $user->isMTC())) {
            abort(403, 'Akses ditolak.');
        }

        $period = $request->get('period', 'monthly');
        $dateFrom = $request->get('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->get('date_to', now()->endOfMonth()->toDateString());

        if ($period === 'daily') {
            $dateFrom = $request->get('date_from', now()->toDateString());
            $dateTo = $dateFrom;
        } elseif ($period === 'weekly') {
            $dateFrom = now()->startOfWeek()->toDateString();
            $dateTo = now()->endOfWeek()->toDateString();
        } elseif ($period === 'monthly') {
            $dateFrom = now()->startOfMonth()->toDateString();
            $dateTo = now()->endOfMonth()->toDateString();
        }

        $scheduleType = $request->get('schedule_type', 'all');
        $technicianId = $request->get('technician_id', 'all');
        $technicians = User::where('role', 'mtc')->orderBy('name')->get();

        $baseQuery = PmCheck::with(['pmSchedule.asset', 'technician', 'checkItems'])
            ->where(function ($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('check_date', [$dateFrom, $dateTo])
                    ->orWhere(function ($fallback) use ($dateFrom, $dateTo) {
                        $fallback->whereNull('check_date')
                            ->whereBetween('due_date', [$dateFrom, $dateTo]);
                    });
            });

        if ($scheduleType !== 'all') {
            $baseQuery->whereHas('pmSchedule', fn($q) => $q->where('schedule_type', $scheduleType));
        }

        if ($technicianId !== 'all') {
            $baseQuery->where('technician_id', $technicianId);
        }

        $pmChecks = $baseQuery->get();
        $closedStatuses = ['approved', 'closed'];
        $completedStatuses = ['completed', 'verified'];
        $notFinishedStatuses = ['pending', 'in_progress', 'waiting_verification'];

        $totalItems = $pmChecks->sum(fn($check) => $check->checkItems->count());
        $doneItems = $pmChecks->sum(fn($check) => $check->checkItems->whereNotNull('condition')->where('condition', '!=', '')->count());
        $notOkItems = $pmChecks->sum(function ($check) {
            return $check->checkItems->filter(function ($item) {
                return $item->condition && !in_array(strtolower($item->condition), ['ok', 'baik', 'normal']);
            })->count();
        });

        $statusSummary = [
            'total' => $pmChecks->count(),
            'belum_usai' => $pmChecks->whereIn('status', $notFinishedStatuses)->count(),
            'selesai' => $pmChecks->whereIn('status', $completedStatuses)->count(),
            'closed' => $pmChecks->whereIn('status', $closedStatuses)->count(),
            'pending' => $pmChecks->where('status', 'pending')->count(),
            'in_progress' => $pmChecks->where('status', 'in_progress')->count(),
            'waiting_verification' => $pmChecks->where('status', 'waiting_verification')->count(),
            'overdue' => $pmChecks->filter(fn($check) => $check->due_date && Carbon::parse($check->due_date)->lt(today()) && !in_array($check->status, array_merge($completedStatuses, $closedStatuses)))->count(),
            'total_items' => $totalItems,
            'done_items' => $doneItems,
            'not_ok_items' => $notOkItems,
            'progress' => $totalItems > 0 ? round(($doneItems / $totalItems) * 100, 1) : 0,
        ];

        $monthReference = Carbon::parse($dateFrom);
        $monthWeeks = collect(range(0, $monthReference->daysInMonth - 1))
            ->map(fn($offset) => $monthReference->copy()->startOfMonth()->addDays($offset)->weekOfYear)
            ->unique()
            ->values();

        $activeMonthSchedules = PmSchedule::with(['asset', 'checklistTemplates' => fn($q) => $q->where('is_active', true)])
            ->where('is_active', true)
            ->whereHas('checklistTemplates', function ($q) use ($monthWeeks) {
                $q->where('is_active', true)
                    ->where(function ($query) use ($monthWeeks) {
                        $query->whereHas('pmSchedule', fn($scheduleQuery) => $scheduleQuery->where('schedule_type', 'daily'));

                        foreach ($monthWeeks as $week) {
                            $query->orWhereJsonContains('active_weeks', $week)
                                ->orWhereJsonContains('active_weeks', (string) $week);
                        }
                    });
            })
            ->get();

        $today = now();
        $todayWeek = (int) $today->weekOfYear;
        $todaySchedules = $this->activePmSchedulesForWeek($todayWeek);
        $todayChecks = PmCheck::whereIn('pm_schedule_id', $todaySchedules->pluck('id'))
            ->where(function ($query) use ($today, $todayWeek) {
                $query->whereDate('check_date', $today->toDateString())
                    ->orWhere('week_number', $todayWeek);
            })
            ->get()
            ->keyBy('pm_schedule_id');

        $todayDoneStatuses = array_merge($completedStatuses, $closedStatuses);
        $todaySummary = [
            'scheduled' => $todaySchedules->count(),
            'not_started' => $todaySchedules->filter(fn($schedule) => !$todayChecks->has($schedule->id))->count(),
            'in_progress' => $todayChecks->whereIn('status', ['pending', 'in_progress', 'waiting_verification'])->count(),
            'done' => $todayChecks->whereIn('status', $todayDoneStatuses)->count(),
        ];

        $currentMonthStart = now()->startOfMonth()->toDateString();
        $currentMonthEnd = now()->endOfMonth()->toDateString();
        $followUpItemsCurrentMonth = PmCheckItem::whereNotNull('next_action')
            ->where('next_action', '!=', '')
            ->whereHas('pmCheck', function ($query) use ($currentMonthStart, $currentMonthEnd) {
                $query->where(function ($dateQuery) use ($currentMonthStart, $currentMonthEnd) {
                    $dateQuery->whereBetween('check_date', [$currentMonthStart, $currentMonthEnd])
                        ->orWhere(function ($fallback) use ($currentMonthStart, $currentMonthEnd) {
                            $fallback->whereNull('check_date')
                                ->whereBetween('due_date', [$currentMonthStart, $currentMonthEnd]);
                        });
                });
            })
            ->get();

        $followUpSummary = [
            'total' => $followUpItemsCurrentMonth->count(),
            'open' => $followUpItemsCurrentMonth->where('follow_up_status', '!=', 'OK')->count(),
            'ok' => $followUpItemsCurrentMonth->where('follow_up_status', 'OK')->count(),
        ];

        $scheduleTypeSummary = $pmChecks
            ->groupBy(fn($check) => $check->pmSchedule->schedule_type ?? 'unknown')
            ->map(function ($items, $type) use ($completedStatuses, $closedStatuses) {
                $closed = $items->whereIn('status', $closedStatuses)->count();
                $completed = $items->whereIn('status', $completedStatuses)->count();
                return [
                    'type' => $type,
                    'total' => $items->count(),
                    'done' => $completed + $closed,
                    'open' => $items->count() - $completed - $closed,
                    'rate' => $items->count() > 0 ? round((($completed + $closed) / $items->count()) * 100, 1) : 0,
                ];
            })->values();

        $pmActivities = TechnicianActivity::where('category', 'PM')
            ->whereDate('start_time', '>=', $dateFrom)
            ->whereDate('start_time', '<=', $dateTo)
            ->get()
            ->groupBy('user_id');

        $technicianPerformance = $technicians->map(function ($tech) use ($pmChecks, $pmActivities, $completedStatuses, $closedStatuses) {
            $checks = $pmChecks->where('technician_id', $tech->id);
            $items = $checks->flatMap->checkItems;
            $doneItemCount = $items->whereNotNull('condition')->where('condition', '!=', '')->count();
            $totalItemCount = $items->count();
            $closedCount = $checks->whereIn('status', $closedStatuses)->count();
            $completedCount = $checks->whereIn('status', $completedStatuses)->count();
            $activityMinutes = ($pmActivities->get($tech->id) ?? collect())->sum(function ($activity) {
                return $activity->status === 'running'
                    ? $activity->start_time->diffInMinutes(now())
                    : ($activity->duration ?? 0);
            });

            return [
                'technician' => $tech,
                'assigned' => $checks->count(),
                'completed' => $completedCount,
                'closed' => $closedCount,
                'not_finished' => $checks->whereIn('status', ['pending', 'in_progress', 'waiting_verification'])->count(),
                'waiting_verification' => $checks->where('status', 'waiting_verification')->count(),
                'total_items' => $totalItemCount,
                'done_items' => $doneItemCount,
                'not_ok_items' => $items->filter(fn($item) => $item->condition && !in_array(strtolower($item->condition), ['ok', 'baik', 'normal']))->count(),
                'completion_rate' => $checks->count() > 0 ? round((($completedCount + $closedCount) / $checks->count()) * 100, 1) : 0,
                'item_rate' => $totalItemCount > 0 ? round(($doneItemCount / $totalItemCount) * 100, 1) : 0,
                'pm_hours' => round($activityMinutes / 60, 2),
            ];
        })->filter(fn($row) => $row['assigned'] > 0 || $row['total_items'] > 0)
            ->sortByDesc('item_rate')
            ->values();

        $overdueChecks = $pmChecks
            ->filter(fn($check) => $check->due_date && Carbon::parse($check->due_date)->lt(today()) && !in_array($check->status, array_merge($completedStatuses, $closedStatuses)))
            ->sortBy('due_date')
            ->take(10)
            ->values();

        $needVerificationChecks = $pmChecks
            ->where('status', 'waiting_verification')
            ->sortBy('updated_at')
            ->take(10)
            ->values();

        $recentFinishedChecks = $pmChecks
            ->whereIn('status', array_merge($completedStatuses, $closedStatuses))
            ->sortByDesc('updated_at')
            ->take(10)
            ->values();

        return view('monitoring.pm', compact(
            'period',
            'dateFrom',
            'dateTo',
            'scheduleType',
            'technicianId',
            'technicians',
            'statusSummary',
            'activeMonthSchedules',
            'todaySummary',
            'followUpSummary',
            'scheduleTypeSummary',
            'technicianPerformance',
            'overdueChecks',
            'needVerificationChecks',
            'recentFinishedChecks'
        ));
    }

    protected function activePmSchedulesForWeek(int $weekNumber)
    {
        return PmSchedule::with(['asset', 'checklistTemplates' => fn($q) => $q->where('is_active', true)])
            ->where('is_active', true)
            ->whereHas('checklistTemplates', function ($q) use ($weekNumber) {
                $q->where('is_active', true)
                    ->where(function ($query) use ($weekNumber) {
                        $query->whereHas('pmSchedule', fn($scheduleQuery) => $scheduleQuery->where('schedule_type', 'daily'))
                            ->orWhereJsonContains('active_weeks', $weekNumber)
                            ->orWhereJsonContains('active_weeks', (string) $weekNumber);
                    });
            })
            ->get();
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
            $running->complete(now());
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
        $activity->complete(now());

        return back()->with('success', 'Aktivitas selesai. Durasi: ' . $activity->duration . ' menit.');
    }

    // MONITORING TIM TEKNISI (Admin Only)
    // MONITORING TIM TEKNISI (Admin Only)
    public function teamMonitoring(Request $request)
    {
        // Pastikan hanya admin yang bisa akses
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        // Filter parameters
        $period = $request->get('period', 'daily');
        $technicianId = $request->input('technician', 'all');

        // 1. TAMBAHAN BARU: Parameter Role Type
        $roleType = $request->input('role_type', 'mtc'); // Default 'mtc' agar tampilan awal tidak berubah

        // CATATAN PENTING: Ubah array ini dengan role bos-bos yang ada di database-mu kawan!
        $bossRoles = ['spv', 'admin', 'manager', 'engineering'];

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

        // 2. AMBIL DATA TECHNICIAN BERDASARKAN ROLE
        $techQuery = User::query();
        if ($roleType === 'mtc') {
            $techQuery->where('role', 'mtc');
        }
        elseif ($roleType === 'management') {
            $techQuery->whereIn('role', $bossRoles);
        }
        else {
            $techQuery->where(function ($q) use ($bossRoles) {
                $q->where('role', 'mtc')->orWhereIn('role', $bossRoles);
            });
        }
        $technicians = $techQuery->orderBy('role')->orderBy('name')->get();

        // 3. QUERY ACTIVITIES
        $query = TechnicianActivity::with(['user', 'ticket.asset', 'ticket.notes.user', 'resumedFromActivity', 'pmCheck.pmSchedule.asset'])
            ->whereDate('start_time', '>=', $dateFrom)
            ->whereDate('start_time', '<=', $dateTo)
            ->orderBy('start_time', 'desc');

        $excludedValues = ['all', '', null];
        if (!in_array($technicianId, $excludedValues, true)) {
            $query->where('user_id', $technicianId);
        }
        else {
            // WAJIB: Batasi aktivitas hanya untuk role yang sedang difilter
            $query->whereIn('user_id', $technicians->pluck('id'));
        }

        $activities = $this->annotatePauseData($query->get());

        // Calculate summary per technician
        $technicianSummary = collect();
        foreach ($technicians as $tech) {
            $techActivities = $activities->where('user_id', $tech->id);

            // Calculate total duration (handle running activities)
            $totalMinutes = $techActivities->sum(function ($act) {
                if ($act->status === 'running') {
                    return $act->start_time->diffInMinutes(now());
                }
                return $act->duration ?? 0;
            });

            // Group by category
            $byCategory = $techActivities->groupBy('category')->map(function ($items) {
                return $items->sum(function ($act) {
                        if ($act->status === 'running') {
                            return $act->start_time->diffInMinutes(now());
                        }
                        return $act->duration ?? 0;
                    }
                    );
                });

            // Get attendance data (clock in/out)
            $attendances = \App\Models\TechnicianAttendance::where('user_id', $tech->id)
                ->whereDate('date', '>=', $dateFrom)
                ->whereDate('date', '<=', $dateTo)
                ->orderBy('date')
                ->get();

            $totalWorkHours = 0;
            $breakMinutes = 0;

            foreach ($attendances as $att) {
                if ($att->clock_in && $att->clock_out) {
                    $dayWorkMinutes = $att->clock_in->diffInMinutes($att->clock_out);
                    $totalWorkHours += ($dayWorkMinutes / 60);
                    $breakMinutes += 60;
                }
                elseif ($att->clock_in && !$att->clock_out) {
                    $dayWorkMinutes = $att->clock_in->diffInMinutes(now());
                    $totalWorkHours += ($dayWorkMinutes / 60);
                }
            }

            $netWorkHours = max(0, $totalWorkHours - ($breakMinutes / 60));
            $productivity = $netWorkHours > 0 ? round(($totalMinutes / 60 / $netWorkHours) * 100, 2) : 0;

            $clockInfo = $attendances->map(function ($att) {
                return [
                'date' => $att->date,
                'clock_in' => $att->clock_in ? $att->clock_in->format('H:i') : '-',
                'clock_out' => $att->clock_out ? $att->clock_out->format('H:i') : 'Running',
                ];
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
                'clock_info' => $clockInfo,
                'total_work_hours' => round($totalWorkHours, 2),
                'net_work_hours' => round($netWorkHours, 2),
                'productivity' => min($productivity, 100),
            ]);
        }

        // Overall summary
        $overallSummary = [
            'total_activities' => $activities->count(),
            'total_minutes' => $activities->sum(function ($act) {
            if ($act->status === 'running') {
                return $act->start_time->diffInMinutes(now());
            }
            return $act->duration ?? 0;
        }),
            'by_category' => $activities->groupBy('category')->map(function ($items) {
            return $items->sum(function ($act) {
                    if ($act->status === 'running') {
                        return $act->start_time->diffInMinutes(now());
                    }
                    return $act->duration ?? 0;
                }
                );
            }),
        ];
        $overallSummary['total_hours'] = round($overallSummary['total_minutes'] / 60, 2);

        $chartLabels = $technicianSummary->pluck('user.name');
        $chartData = $technicianSummary->pluck('total_hours');

        // ========== PM PROGRESS DATA ==========
        $currentWeek = now()->weekOfYear;
        $pmProgressByWeek = collect();
        $weeksToShow = [$currentWeek];

        for ($i = 3; $i >= 0; $i--) {
            $weeksToShow[] = $currentWeek - $i;
        }
        $weeksToShow = array_unique(array_filter(array_values($weeksToShow), function ($w) {
            return $w > 0 && $w <= 52;
        }));
        sort($weeksToShow);

        foreach ($weeksToShow as $week) {
            $schedules = PmSchedule::where('is_active', true)
                ->where('schedule_type', 'weekly')
                ->whereHas('checklistTemplates', function ($q) use ($week) {
                $q->where('is_active', true)
                    ->where(function ($query) use ($week) {
                    $query->whereJsonContains('active_weeks', $week)
                        ->orWhereJsonContains('active_weeks', (string)$week);
                }
                );
            })->with(['checklistTemplates' => function ($q) use ($week) {
                $q->where('is_active', true);
            }])->get();

            $totalItems = 0;
            foreach ($schedules as $schedule) {
                $activeWeeksTemplates = $schedule->checklistTemplates->filter(function ($template) use ($week) {
                    $activeWeeks = is_array($template->active_weeks) ? $template->active_weeks : json_decode($template->active_weeks, true);
                    return is_array($activeWeeks) && in_array($week, $activeWeeks);
                });
                $totalItems += $activeWeeksTemplates->count();
            }

            $completedItems = PmCheckItem::whereHas('pmCheck', function ($q) use ($week) {
                $q->where('week_number', $week)
                    ->whereIn('status', ['completed', 'verified', 'approved']);
            })->whereNotNull('condition')->count();

            $inProgressItems = PmCheckItem::whereHas('pmCheck', function ($q) use ($week) {
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

        $pmChecksQuery = PmCheck::with(['pmSchedule.asset', 'checkItems', 'technician'])
            ->where('week_number', $currentWeek);

        $excludedValuesPm = ['all', '', null];
        if (!in_array($technicianId, $excludedValuesPm, true)) {
            $pmChecksQuery->where('technician_id', $technicianId);
        }
        else {
            // Batasi juga PM Checks berdasarkan Kategori Personel
            $pmChecksQuery->whereIn('technician_id', $technicians->pluck('id'));
        }

        $pmChecks = $pmChecksQuery->get();
        $pmChecksByTech = $pmChecks->groupBy('technician_id');

        foreach ($pmChecksByTech as $picId => $checks) {
            $technician = User::find($picId);
            if (!$technician)
                continue;

            $totalItems = 0;
            $completedItems = 0;
            $inProgressItems = 0;
            $machines = [];

            foreach ($checks as $check) {
                $checkItems = $check->checkItems;
                $totalItems += $checkItems->count();

                $itemsWithCondition = $checkItems->whereNotNull('condition')->count();
                $completedItems += $itemsWithCondition;

                if (in_array($check->status, ['completed', 'verified', 'approved'])) {
                    $inProgressItems += $itemsWithCondition;
                }

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

        $pmProgressByPic = $pmProgressByPic->sortByDesc('percentage')->values();

        return view('monitoring.team', compact(
            'activities', 'technicians', 'technicianSummary', 'overallSummary',
            'period', 'technicianId', 'dateFrom', 'dateTo', 'roleType', // <--- roleType dipassing
            'chartLabels', 'chartData',
            'pmProgressByWeek', 'pmProgressByPic', 'currentWeek'
        ));
    }

    // EXPORT TEAM MONITORING TO EXCEL
    // EXPORT TEAM MONITORING TO EXCEL
    public function exportTeamMonitoring(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $period = $request->get('period', 'daily');
        $technicianId = $request->get('technician', 'all');
        $roleType = $request->input('role_type', 'mtc');

        // SESUAIKAN ARRAY INI DENGAN ROLE BOS DI DATABASE KAMU YA KAWAN
        $bossRoles = ['spv', 'manager', 'engineering'];

        $dateFrom = $request->get('date_from', now()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());

        switch ($period) {
            case 'weekly':
                $dateFrom = now()->startOfWeek()->toDateString();
                $dateTo = now()->endOfWeek()->toDateString();
                break;
            case 'monthly':
                $dateFrom = now()->startOfMonth()->toDateString();
                $dateTo = now()->endOfMonth()->toDateString();
                break;
            default:
                $dateFrom = $request->get('date_from', now()->toDateString());
                $dateTo = $request->get('date_to', now()->toDateString());
        }

        $techQuery = User::query();
        if ($roleType === 'mtc') {
            $techQuery->where('role', 'mtc');
        }
        elseif ($roleType === 'management') {
            $techQuery->whereIn('role', $bossRoles);
        }
        else {
            $techQuery->where(function ($q) use ($bossRoles) {
                $q->where('role', 'mtc')->orWhereIn('role', $bossRoles);
            });
        }
        $technicians = $techQuery->orderBy('role')->orderBy('name')->get();

        $query = TechnicianActivity::with(['user', 'ticket.asset', 'ticket.notes.user', 'resumedFromActivity', 'pmCheck.pmSchedule.asset'])
            ->whereDate('start_time', '>=', $dateFrom)
            ->whereDate('start_time', '<=', $dateTo)
            ->orderBy('start_time', 'desc');

        $excludedValues = ['all', '', null];
        if (!in_array($technicianId, $excludedValues, true)) {
            $query->where('user_id', $technicianId);
        }
        else {
            $query->whereIn('user_id', $technicians->pluck('id'));
        }

        $activities = $this->annotatePauseData($query->get());

        $technicianSummary = collect();
        foreach ($technicians as $tech) {
            $techActivities = $activities->where('user_id', $tech->id);
            $totalMinutes = $techActivities->sum(function ($act) {
                if ($act->status === 'running') {
                    return $act->start_time->diffInMinutes(now());
                }
                return $act->duration ?? 0;
            });
            $byCategory = $techActivities->groupBy('category')->map(function ($items) {
                return $items->sum(function ($act) {
                        if ($act->status === 'running') {
                            return $act->start_time->diffInMinutes(now());
                        }
                        return $act->duration ?? 0;
                    }
                    );
                });

            // ====================================================================
            // TAMBAHAN LOGIKA CLOCK IN/OUT UNTUK EXPORT EXCEL
            // ====================================================================
            $attendances = \App\Models\TechnicianAttendance::where('user_id', $tech->id)
                ->whereDate('date', '>=', $dateFrom)
                ->whereDate('date', '<=', $dateTo)
                ->orderBy('date')
                ->get();

            $totalWorkHours = 0;
            $breakMinutes = 0;

            foreach ($attendances as $att) {
                if ($att->clock_in && $att->clock_out) {
                    $dayWorkMinutes = $att->clock_in->diffInMinutes($att->clock_out);
                    $totalWorkHours += ($dayWorkMinutes / 60);
                    $breakMinutes += 60; // Asumsi 1 jam istirahat
                }
                elseif ($att->clock_in && !$att->clock_out) {
                    $dayWorkMinutes = $att->clock_in->diffInMinutes(now());
                    $totalWorkHours += ($dayWorkMinutes / 60);
                }
            }

            $netWorkHours = max(0, $totalWorkHours - ($breakMinutes / 60));
            $productivity = $netWorkHours > 0 ? round(($totalMinutes / 60 / $netWorkHours) * 100, 2) : 0;

            $clockInfo = $attendances->map(function ($att) {
                return [
                'date' => $att->date,
                'clock_in' => $att->clock_in ? $att->clock_in->format('H:i') : '-',
                'clock_out' => $att->clock_out ? $att->clock_out->format('H:i') : 'Running',
                ];
            });
            // ====================================================================

            $technicianSummary->push([
                'user' => $tech,
                'total_activities' => $techActivities->count(),
                'total_minutes' => $totalMinutes,
                'total_hours' => round($totalMinutes / 60, 2),
                'by_category' => $byCategory,
                'pm_count' => $techActivities->where('category', 'PM')->count(),
                'breakdown_count' => $techActivities->where('category', 'Breakdown')->count(),
                'other_count' => $techActivities->where('category', 'Lain-lain')->count(),

                // Variabel baru ini dipassing agar Sheet 3 & Sheet 6 di Excel bisa baca datanya
                'clock_info' => $clockInfo,
                'total_work_hours' => round($totalWorkHours, 2),
                'net_work_hours' => round($netWorkHours, 2),
                'productivity' => min($productivity, 100),
            ]);
        }

        $overallSummary = [
            'total_activities' => $activities->count(),
            'total_minutes' => $activities->sum(function ($act) {
            if ($act->status === 'running') {
                return $act->start_time->diffInMinutes(now());
            }
            return $act->duration ?? 0;
        }),
            'by_category' => $activities->groupBy('category')->map(function ($items) {
            return $items->sum(function ($act) {
                    if ($act->status === 'running') {
                        return $act->start_time->diffInMinutes(now());
                    }
                    return $act->duration ?? 0;
                }
                );
            }),
        ];
        $overallSummary['total_hours'] = round($overallSummary['total_minutes'] / 60, 2);

        $filename = 'Monitoring_Tim_Teknisi_' . $period . '_' . str_replace('/', '_', $dateFrom) . '_sd_' . str_replace('/', '_', $dateTo) . '.xlsx';

        $export = new \App\Exports\TechnicianActivityExport(
            $activities, $technicians, $technicianSummary, $overallSummary,
            $period, $dateFrom, $dateTo, $technicianId
            );

        return \Maatwebsite\Excel\Facades\Excel::download($export, $filename);
    }

    protected function annotatePauseData(Collection $activities): Collection
    {
        $breakdownActivitiesByTicket = $activities
            ->where('category', 'Breakdown')
            ->groupBy('reference_id')
            ->map(fn($items) => $items->sortBy('start_time')->values());

        return $activities->map(function ($activity) use ($breakdownActivitiesByTicket) {
            $activity->pause_context = $this->resolvePauseContext(
                $activity,
                $breakdownActivitiesByTicket->get($activity->reference_id, collect())
            );

            return $activity;
        });
    }

    protected function resolvePauseContext(TechnicianActivity $activity, Collection $ticketActivities): array
    {
        $context = [
            'pending_at' => $activity->paused_at,
            'pending_label' => $activity->paused_at ? 'Pending teknisi' : null,
            'pending_note' => $activity->pause_reason,
            'resumed_at' => $activity->resumed_at,
        ];

        if ($activity->category !== 'Breakdown' || !$activity->ticket) {
            return $context;
        }

        $orderedActivities = $ticketActivities->values();
        $currentIndex = $orderedActivities->search(fn($item) => (int) $item->id === (int) $activity->id);
        $nextActivity = $currentIndex !== false ? $orderedActivities->get($currentIndex + 1) : null;

        $pendingKeywords = [
            'Pengerjaan di-PENDING',
            'MENUNGGU SPAREPART',
            'KOORDINASI DENGAN PRODUKSI',
            'PERBAIKAN PIHAK EKSTERNAL',
        ];

        $pendingNote = $activity->ticket->notes
            ->sortBy('created_at')
            ->first(function ($note) use ($activity, $nextActivity, $pendingKeywords) {
                $matchesKeyword = collect($pendingKeywords)->contains(
                    fn($keyword) => str_contains($note->note, $keyword)
                );

                if (!$matchesKeyword || $note->created_at->lt($activity->start_time)) {
                    return false;
                }

                if ($nextActivity && $note->created_at->gt($nextActivity->start_time)) {
                    return false;
                }

                return true;
            });

        if ($pendingNote && !$context['pending_at']) {
            $context['pending_at'] = $pendingNote->created_at;
            $context['pending_label'] = $this->extractPendingLabel($pendingNote->note);
            $context['pending_note'] = $pendingNote->note;
        }

        if (!$context['resumed_at'] && $nextActivity) {
            $context['resumed_at'] = $nextActivity->start_time;
        }

        return $context;
    }

    protected function extractPendingLabel(?string $note): ?string
    {
        if (!$note) {
            return null;
        }

        if (str_contains($note, 'MENUNGGU SPAREPART')) {
            return 'Menunggu sparepart';
        }

        if (str_contains($note, 'KOORDINASI DENGAN PRODUKSI')) {
            return 'Koordinasi produksi';
        }

        if (str_contains($note, 'PERBAIKAN PIHAK EKSTERNAL')) {
            return 'Perbaikan eksternal';
        }

        if (str_contains($note, 'Pengerjaan di-PENDING')) {
            return 'Pending teknisi';
        }

        return 'Jeda aktivitas';
    }
}
