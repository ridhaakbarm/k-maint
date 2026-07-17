<?php

namespace App\Http\Controllers;

use App\Models\PmCheck;
use App\Models\PmSchedule;
use App\Models\PmCheckItem;
use App\Models\User;
use App\Models\TechnicianActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PmCheckController extends Controller
{
    /**
     * Tampilan Utama Eksekusi PM (Monitoring Week)
     */
    public function index(Request $request, $scheduleType = 'weekly')
    {
        $user = Auth::user();
        $currentWeek = $request->query('week') ? (int) $request->query('week') : (int) now()->weekOfYear;
        $currentDate = $request->query('date')
            ? Carbon::parse($request->query('date'))->toDateString()
            : now()->toDateString();
        $search = $request->query('search');

        // ========== TEKNISI & ADMIN: Keduanya melihat semua jadwal PM yang aktif ==========
        $schedulesQuery = PmSchedule::with(['asset', 'checklistTemplates'])
            ->where('schedule_type', $scheduleType)
            ->where('is_active', true);

        if ($scheduleType === 'daily') {
            $schedulesQuery->whereHas('checklistTemplates', function($q) {
                $q->where('is_active', true);
            });
        } else {
            $schedulesQuery->whereHas('checklistTemplates', function($q) use ($currentWeek) {
                $q->where('is_active', true)
                  ->where(function($query) use ($currentWeek) {
                      $query->whereJsonContains('active_weeks', $currentWeek)
                            ->orWhereJsonContains('active_weeks', (string)$currentWeek);
                  });
            });
        }

        // Apply search filter untuk Daftar Tugas juga
        if ($search) {
            $schedulesQuery->whereHas('asset', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $schedules = $schedulesQuery->get();

        $existingChecksBySchedule = PmCheck::withCount([
                'checkItems as total_items_count',
                'checkItems as done_items_count' => function($query) {
                    $query->whereNotNull('condition')
                          ->where('condition', '!=', '');
                }
            ])
            ->whereIn('pm_schedule_id', $schedules->pluck('id'))
            ->when($scheduleType === 'daily', function($query) use ($currentDate) {
                return $query->whereDate('check_date', $currentDate);
            }, function($query) use ($currentWeek) {
                return $query->where('week_number', $currentWeek);
            })
            ->get()
            ->keyBy('pm_schedule_id');

        // Tentukan viewMode berdasarkan role
        $viewMode = $user->isMTC() ? 'technician_master' : 'admin_master';

        // Riwayat PM dengan Filter Status & Search
        $statusFilter = $request->query('status');

        $allChecksQuery = PmCheck::with(['pmSchedule.asset', 'technician'])
            ->withCount([
                'checkItems as total_items_count',
                'checkItems as done_items_count' => function($query) {
                    $query->whereNotNull('condition')
                          ->where('condition', '!=', '');
                }
            ])
            ->whereHas('pmSchedule', function($q) use ($scheduleType) {
                $q->where('schedule_type', $scheduleType);
            });

        $allChecks = $allChecksQuery
            ->when($scheduleType === 'daily', function($query) use ($currentDate) {
                return $query->whereDate('check_date', $currentDate);
            })
            ->when($scheduleType !== 'daily', function($query) use ($currentWeek) {
                return $query->where('week_number', $currentWeek);
            })
            ->when($statusFilter, function($query) use ($statusFilter) {
                return $query->where('status', $statusFilter);
            })
            ->when($search, function($query) use ($search) {
                // Search berdasarkan nama mesin atau nama teknisi
                return $query->whereHas('pmSchedule.asset', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })
                ->orWhere('technician_name', 'like', "%{$search}%");
            })
            ->latest()
            ->limit(20)
            ->get();

        return view('pm-checks.index', compact(
            'schedules',
            'scheduleType',
            'viewMode',
            'allChecks',
            'currentWeek',
            'currentDate',
            'search',
            'existingChecksBySchedule'
        ));
    }

    /**
     * Form Persiapan Checklist
     */
    public function create($scheduleId)
    {
        if (!Auth::user()->isAdmin() && !Auth::user()->isGA() && !Auth::user()->isMTC()) {
            abort(403, 'Akses ditolak.');
        }

        $schedule = PmSchedule::with(['asset', 'checklistTemplates' => function($q) {
            $q->where('is_active', true)->orderBy('order');
        }])->findOrFail($scheduleId);

        // VALIDASI: MTC hanya bisa mulai cek pada week yang sedang berjalan
        if (Auth::user()->isMTC()) {
            if ($schedule->schedule_type === 'daily') {
                $requestedDate = request()->query('date', now()->toDateString());

                if ($requestedDate !== now()->toDateString()) {
                    return redirect()->route('pm.execution.index', ['scheduleType' => 'daily'])
                        ->with('error', 'Akses ditolak. Sebagai teknisi MTC, Anda hanya bisa memulai PM Daily untuk tanggal hari ini.');
                }
            } else {
                $requestedWeek = (int) request()->query('week');
                $actualCurrentWeek = (int) now()->weekOfYear;

                if ($requestedWeek !== $actualCurrentWeek) {
                    return redirect()->route('pm.execution.index', ['scheduleType' => $schedule->schedule_type])
                        ->with('error', "Akses ditolak. Sebagai teknisi MTC, Anda hanya bisa memulai PM pada Week {$actualCurrentWeek} (week yang sedang berjalan).");
                }
            }
        }

        return view('pm-checks.create', compact('schedule'));
    }

    /**
     * Simpan Awal Checklist & Mulai Monitoring Aktivitas
     */
    public function store(Request $request, $scheduleId)
    {
        if (!Auth::user()->isAdmin() && !Auth::user()->isGA() && !Auth::user()->isMTC()) {
            abort(403);
        }

        $schedule = PmSchedule::with('asset')->findOrFail($scheduleId);

        $request->validate([
            'check_date' => 'required|date',
            'shift' => 'required|string|max:255',
            'technician_name' => 'required|string|max:255',
        ]);

        $checkDate = Carbon::parse($request->check_date);
        $weekNumber = $request->query('week') ? (int)$request->query('week') : $checkDate->weekOfYear;
        $isDaily = $schedule->schedule_type === 'daily';

        // VALIDASI TAMBAHAN: MTC hanya bisa menyimpan pada week yang sedang berjalan
        if (Auth::user()->isMTC()) {
            $actualCurrentWeek = (int) now()->weekOfYear;

            if ($isDaily && !$checkDate->isSameDay(now())) {
                return redirect()->route('pm.execution.index', ['scheduleType' => 'daily'])
                    ->with('error', 'Akses ditolak. Sebagai teknisi MTC, Anda hanya bisa membuat PM Daily untuk tanggal hari ini.');
            }

            if (!$isDaily && $weekNumber !== $actualCurrentWeek) {
                return redirect()->route('pm.execution.index', ['scheduleType' => $schedule->schedule_type])
                    ->with('error', "Akses ditolak. Sebagai teknisi MTC, Anda hanya bisa membuat PM pada Week {$actualCurrentWeek} (week yang sedang berjalan).");
            }
        }

        $existingCheck = PmCheck::where('pm_schedule_id', $schedule->id)
            ->when($isDaily, function($query) use ($checkDate) {
                return $query->whereDate('check_date', $checkDate->toDateString());
            }, function($query) use ($weekNumber) {
                return $query->where('week_number', $weekNumber);
            })
            ->first();

        if ($existingCheck) {
            return redirect()->route('pm.execution.show', $existingCheck->id)
                ->with('error', 'Checklist PM untuk periode ini sudah dibuat.');
        }

        $pmCheck = PmCheck::create([
            'pm_schedule_id' => $schedule->id,
            'technician_id' => Auth::id(),
            'check_date' => $request->check_date,
            'due_date' => $request->check_date,
            'shift' => $request->shift,
            'technician_name' => $request->technician_name,
            'week_number' => $weekNumber,
            'status' => 'in_progress',
        ]);

        // INTEGRASI MONITORING: Kirim Nama Mesin ke Description
        $desc = "PM: " . ($schedule->asset->name ?? 'Unit Unknown');
        $this->logActivity('PM', $pmCheck->id, $desc);

        // Generate Item Checklist
        $templates = $schedule->checklistTemplates()->where('is_active', true)->get();
        foreach ($templates as $template) {
            $activeWeeks = is_array($template->active_weeks) ? $template->active_weeks : [];
            if ($isDaily || in_array($weekNumber, $activeWeeks) || in_array((string) $weekNumber, $activeWeeks)) {
                PmCheckItem::create([
                    'pm_check_id' => $pmCheck->id,
                    'checklist_template_id' => $template->id,
                ]);
            }
        }

        return redirect()->route('pm.execution.show', $pmCheck->id)
            ->with('success', $isDaily ? 'Checklist PM Daily berhasil dibuat.' : "Checklist Week $weekNumber berhasil dibuat.");
    }

    /**
     * Detail Pengerjaan Checklist
     */
    public function show($id)
    {
        $pmCheck = PmCheck::with([
            'checkItems.checklistTemplate',
            'checkItems.verifiedBy',
            'pmSchedule.asset',
            'technician'
        ])->findOrFail($id);

        $verificationUsers = User::whereIn('role', ['admin', 'ga'])->get();

        // FLAG READ-ONLY: MTC hanya bisa edit jika week yang sedang berjalan
        $readOnly = false;
        $isCurrentPeriod = $pmCheck->pmSchedule->schedule_type === 'daily'
            ? $pmCheck->check_date && $pmCheck->check_date->isSameDay(now())
            : ($pmCheck->week_number === (int) now()->weekOfYear);

        if (Auth::user()->isMTC() && !$isCurrentPeriod) {
            $readOnly = true;
        }

        return view('pm-checks.show', compact('pmCheck', 'verificationUsers', 'readOnly'));
    }

    /**
     * Preview Template Checklist (Read-Only untuk MTC melihat week lain)
     */
    public function previewTemplate($scheduleId)
    {
        $schedule = PmSchedule::with([
            'asset',
            'checklistTemplates' => function($q) {
                $q->where('is_active', true)->orderBy('order');
            }
        ])->findOrFail($scheduleId);

        $weekNumber = (int) request()->query('week', now()->weekOfYear);
        $selectedDate = request()->query('date', now()->toDateString());
        $isDaily = $schedule->schedule_type === 'daily';

        // Filter template yang aktif untuk week ini
        $activeTemplates = $schedule->checklistTemplates->filter(function($template) use ($weekNumber, $isDaily) {
            if ($isDaily) {
                return true;
            }

            $activeWeeks = is_array($template->active_weeks) ? $template->active_weeks : json_decode($template->active_weeks, true);
            return is_array($activeWeeks) && (in_array($weekNumber, $activeWeeks) || in_array((string) $weekNumber, $activeWeeks));
        });

        $existingPmCheck = PmCheck::where('pm_schedule_id', $schedule->id)
            ->when($isDaily, function($query) use ($selectedDate) {
                return $query->whereDate('check_date', $selectedDate);
            }, function($query) use ($weekNumber) {
                return $query->where('week_number', $weekNumber);
            })
            ->first();

        $canStartChecklist = !$existingPmCheck && (
            Auth::user()->isAdmin() ||
            Auth::user()->isGA() ||
            (Auth::user()->isMTC() && (
                ($isDaily && $selectedDate === now()->toDateString()) ||
                (!$isDaily && $weekNumber === (int) now()->weekOfYear)
            ))
        );

        return view('pm-checks.preview', compact(
            'schedule',
            'weekNumber',
            'selectedDate',
            'activeTemplates',
            'existingPmCheck',
            'canStartChecklist'
        ));
    }

    /**
     * Update Item Checklist & Verifikasi Admin
     */
    /**
     * Update Item Checklist & Record Real-time Waktu
     */
public function batchUpdateItems(Request $request, $checkId)
{
    $pmCheck = PmCheck::findOrFail($checkId);
    $user = Auth::user();
    $finalVerifyRequested = $request->boolean('final_verify');

    $canSave = false;
    if (($user->isAdmin() || $user->isGA() || $user->isMTC()) && $pmCheck->status == 'in_progress') $canSave = true;
    
    // REVISI: Tambahkan 'waiting_verification' agar Admin bisa memverifikasi item di status ini
    if ($user->isAdmin() && in_array($pmCheck->status, ['waiting_verification', 'completed', 'verified'])) $canSave = true;
    
    if ($user->isManager() && in_array($pmCheck->status, ['verified', 'approved'])) $canSave = true;

        if (!$canSave) {
            return back()->with('error', 'Anda tidak diizinkan menyimpan pada status ini.');
        }

        foreach ($request->input('items', []) as $itemId => $itemData) {
            $item = PmCheckItem::where('pm_check_id', $pmCheck->id)->find($itemId);
            if (!$item) continue;

            $updates = [];

            if ($pmCheck->status == 'in_progress' || $user->isAdmin()) {
                // LOGIKA BARU: Jika hasil (condition) diisi, catat waktu dan usernya
                if (isset($itemData['condition']) && !empty($itemData['condition'])) {
                    $updates['condition'] = $itemData['condition'];
                    
                    // Catat hanya jika checked_at masih kosong (supaya waktu pertama kali tidak berubah)
                    if (empty($item->checked_at)) {
                        $updates['checked_at'] = now(); // Mencatat jam saat tombol simpan ditekan
                        $updates['checked_by_user_id'] = $user->id; // Mencatat siapa teknisinya
                    }
                }

                if (isset($itemData['action_taken'])) $updates['action_taken'] = $itemData['action_taken'];
                if (isset($itemData['next_action'])) $updates['next_action'] = $itemData['next_action'];

                if ($request->hasFile("items.$itemId.photo_before")) {
                    $updates['photo_before'] = $request->file("items.$itemId.photo_before")->store('pm-photos/before', 'public');
                }
            }

            // Verifikasi Admin (Checklist Switch)
            if ($user->isAdmin()) {
                if (isset($itemData['is_verified']) && $itemData['is_verified'] == '1') {
                    $updates['verified_by_user_id'] = $user->id;
                } else {
                    $updates['verified_by_user_id'] = null;
                }
            }

            if (!empty($updates)) {
                $item->update($updates);
            }
        }

        if ($finalVerifyRequested) {
            if (!$user->isAdmin()) {
                abort(403, 'Akses ditolak.');
            }

            if ($pmCheck->status !== 'waiting_verification') {
                return back()->with('error', 'Verifikasi akhir hanya bisa dilakukan saat status PM menunggu verifikasi.');
            }

            $pmCheck->load(['checkItems', 'pmSchedule']);
            $allVerified = $pmCheck->checkItems->every(fn($item) => !empty($item->verified_by_user_id));

            if (!$allVerified) {
                return back()->with('error', 'Gagal! Masih ada item yang belum diverifikasi.');
            }

            $pmCheck->update([
                'status' => 'completed',
                'verified_at' => now(),
                'admin_id' => $user->id
            ]);

            return redirect()->route('pm.execution.index', [
                    'scheduleType' => $pmCheck->pmSchedule->schedule_type ?? 'weekly',
                    'week' => $pmCheck->week_number,
                    'date' => optional($pmCheck->check_date)->toDateString(),
                ])
                ->with('success', 'Perubahan disimpan dan pekerjaan PM berhasil diverifikasi.');
        }

        return back()->with('success', 'Perubahan disimpan. Waktu pengecekan telah dicatat secara real-time!');
    }

    /**
     * Mulai Pengerjaan Kembali & Monitoring
     */
    public function startWork(Request $request, $id)
{
    $pmCheck = PmCheck::with('pmSchedule.asset')->findOrFail($id);

    // Update status ke in_progress jika masih pending
    $pmCheck->update(['status' => 'in_progress']);

    // Mencatat aktivitas ke Monitoring Board
    $description = "Lanjut PM: " . ($pmCheck->pmSchedule->asset->name ?? 'Unit Unknown');
    $this->logActivity('PM', $pmCheck->id, $description);

    // REVISI: Jangan gunakan back(), tapi arahkan ke halaman detail
    return redirect()->route('pm.execution.show', $pmCheck->id)
        ->with('success', 'Aktivitas PM dilanjutkan dan sedang direkam.');
}

    /**
     * Selesaikan Checklist & Tutup Monitoring
     */
    public function complete($id)
{
    $pmCheck = PmCheck::with('pmSchedule')->where('id', $id)
        ->where(function($query) {
            $query->where('technician_id', Auth::id())
                  ->orWhere(fn($q) => Auth::user()->isAdmin());
        })
        ->where('status', 'in_progress')
        ->firstOrFail();

    // REVISI: Status menjadi waiting_verification (Menunggu Verifikasi)
    $pmCheck->update(['status' => 'waiting_verification']);

    // Tutup aktivitas di Monitoring Board
    TechnicianActivity::where('user_id', Auth::id())
        ->where('status', 'running')
        ->update(['status' => 'completed', 'end_time' => now()]);

    // Redirect ke index karena tugas sudah selesai dari sisi teknisi
    return redirect()->route('pm.execution.index', [
            'scheduleType' => $pmCheck->pmSchedule->schedule_type ?? 'weekly',
            'week' => $pmCheck->week_number,
            'date' => optional($pmCheck->check_date)->toDateString(),
        ])
        ->with('success', 'PM telah diselesaikan dan sekarang menunggu verifikasi Admin.');
}

    /**
     * LOGIKA INTERNAL: Pencatatan ke Monitoring Board
     */
    private function logActivity($category, $referenceId, $description = null)
    {
        // 1. Berhentikan tugas lama teknisi ini yang masih 'running'
        TechnicianActivity::where('user_id', Auth::id())
            ->where('status', 'running')
            ->update([
                'status' => 'completed', 
                'end_time' => now()
            ]);

        // 2. Buat log aktivitas baru
        TechnicianActivity::create([
            'user_id' => Auth::id(),
            'category' => $category,
            'reference_id' => $referenceId,
            'description' => $description,
            'start_time' => now(),
            'status' => 'running'
        ]);
    }

    /**
 * Verifikasi Akhir oleh Admin untuk mengubah status ke COMPLETED
 */
public function verify($id)
{
    $pmCheck = PmCheck::with(['checkItems', 'pmSchedule'])->findOrFail($id);
    
    if (!Auth::user()->isAdmin()) {
        abort(403, 'Akses ditolak.');
    }

    // Validasi keamanan: Pastikan semua item di database memang sudah dicentang
    $allVerified = $pmCheck->checkItems->every(fn($item) => !empty($item->verified_by_user_id));

    if (!$allVerified) {
        return back()->with('error', 'Gagal! Masih ada item yang belum diverifikasi.');
    }

    $pmCheck->update([
        'status' => 'completed',
        'verified_at' => now(), // Catat waktu verifikasi
        'admin_id' => Auth::id() // Catat siapa Admin yang memverifikasi
    ]);

    return redirect()->route('pm.execution.index', [
            'scheduleType' => $pmCheck->pmSchedule->schedule_type ?? 'weekly',
            'week' => $pmCheck->week_number,
            'date' => optional($pmCheck->check_date)->toDateString(),
        ])
        ->with('success', 'Pekerjaan PM telah diverifikasi dan status kini COMPLETED.');
}
}
