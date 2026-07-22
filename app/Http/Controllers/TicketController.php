<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use App\Models\Notification;
use App\Models\Asset;
use App\Models\MachinePart; 
use App\Models\TicketStatusHistory;
use App\Models\TechnicianActivity; // Import Model Monitoring
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TicketController extends Controller
{
    /**
     * Dashboard Monitoring (Admin/GA/MTC)
     */
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', 'all');

        $query = Ticket::query();

        if ($user->isUser()) {
            // Jika user dari departemen produksi, tampilkan semua tiket produksi
            if ($user->department === 'produksi') {
                $query->whereHas('requester', function($q) {
                    $q->where('department', 'produksi');
                });
            } else {
                // User dari departemen lain hanya melihat tiket mereka sendiri
                $query->where('requester_id', $user->id);
            }
        }

        if ($year != 'all') { $query->whereYear('request_date', $year); }
        if ($month != 'all') { $query->whereMonth('request_date', $month); }

        $statistics = [
            'total' => $query->count(),
            'open' => $query->clone()->where('status', 'open')->count(),
            'onprogress' => $query->clone()->where('status', 'onprogress')->count(),
            'request_to_close' => $query->clone()->where('status', 'request_to_close')->count(),
            'closed' => $query->clone()->where('status', 'closed')->count(),
            'schedule' => $query->clone()->where('status', 'schedule')->count(),
            'rejected' => $query->clone()->where('status', 'rejected')->count(),
        ];

        // Ambil daftar tiket yang sedang On Progress untuk ditampilkan di Dashboard
        $onProgressTickets = Ticket::with(['requester', 'asset', 'machinePart'])
            ->where('status', 'onprogress');

        if ($user->isUser()) {
            // Jika user dari departemen produksi, tampilkan semua tiket produksi
            if ($user->department === 'produksi') {
                $onProgressTickets->whereHas('requester', function($q) {
                    $q->where('department', 'produksi');
                });
            } else {
                // User dari departemen lain hanya melihat tiket mereka sendiri
                $onProgressTickets->where('requester_id', $user->id);
            }
        }

        $onProgressTickets = $onProgressTickets->latest()->get();

        $newTicketsCount = ($user->isAdmin() || $user->isGA() || $user->isMTC()) ? Ticket::getNewTicketsCount() : 0;

        $availableYears = Ticket::selectRaw('YEAR(request_date) as year')->distinct()->orderBy('year', 'desc')->pluck('year');
        if ($availableYears->isEmpty()) { $availableYears = collect([date('Y')]); }

        return view('dashboard', compact(
            'statistics',
            'onProgressTickets',
            'newTicketsCount',
            'year',
            'month',
            'availableYears'
        ));
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Ticket::with(['requester', 'asset', 'machinePart']);

        if ($request->has('status') && $request->status != '') { $query->where('status', $request->status); }
        if ($request->has('year') && $request->year != 'all') { $query->whereYear('request_date', $request->year); }

        if ($user->isAdmin() || $user->isGA() || $user->isMTC()) {
            // Admin, GA, dan MTC melihat semua tiket
            $tickets = $query->latest()->get();
        } elseif ($user->department === 'produksi') {
            // User dari departemen produksi melihat semua tiket dari departemen produksi
            $tickets = $query->whereHas('requester', function($q) {
                $q->where('department', 'produksi');
            })->latest()->get();
        } else {
            // User dari departemen lain hanya melihat tiket mereka sendiri
            $tickets = $query->where('requester_id', $user->id)->latest()->get();
        }

        $newTicketsCount = ($user->isAdmin() || $user->isGA() || $user->isMTC()) ? Ticket::getNewTicketsCount() : 0;

        return view('tickets.index', compact('tickets', 'newTicketsCount'));
    }

    public function create()
    {
        $generatedTicketNo = Ticket::generateTicketNo();
        $assets = Asset::all();
        $machineParts = MachinePart::all(); 

        return view('tickets.create', compact('assets', 'machineParts', 'generatedTicketNo'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'ticket_no' => 'required',
            'asset_id' => 'required|exists:assets,id',
            //'machine_part_id' => 'required|exists:machine_parts,id',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'attachment' => 'nullable|image|max:10048',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('attachments'), $fileName);
            $attachmentPath = 'attachments/' . $fileName;
        }

        $ticket = Ticket::create([
            'ticket_no' => $request->ticket_no,
            'request_date' => now(),
            'requester_id' => Auth::id(),
            'asset_id' => $request->asset_id,
           // 'machine_part_id' => $request->machine_part_id,
            'subject' => $request->subject,
            'description' => $request->description,
            'attachment' => $attachmentPath,
            'status' => 'open',
        ]);

        return redirect()->route('tickets.index')->with('success', 'Ticket ' . $ticket->ticket_no . ' berhasil dibuat!');
    }

    public function show(Ticket $ticket)
    {
        $user = Auth::user();

        // Admin, GA, dan MTC bisa melihat semua tiket
        if (!$user->isAdmin() && !$user->isGA() && !$user->isMTC()) {
            // Jika user dari departemen produksi, bisa melihat semua tiket produksi
            if ($user->department === 'produksi') {
                // Cek apakah tiket ini dibuat oleh user dari departemen produksi
                if ($ticket->requester->department !== 'produksi') {
                    abort(403, 'Anda tidak memiliki akses ke tiket ini.');
                }
            } else {
                // User dari departemen lain hanya bisa melihat tiket mereka sendiri
                if ($ticket->requester_id !== $user->id) {
                    abort(403, 'Anda tidak memiliki akses ke tiket ini.');
                }
            }
        }

        $ticket->load(['statusHistories.user', 'asset', 'machinePart']);
        return view('tickets.show', compact('ticket'));
    }

    /**
     * UPDATE STATUS & INTEGRASI MONITORING BOARD
     */
    public function updateStatus(Request $request, Ticket $ticket)
    {
        $user = Auth::user();
        
        if ($user->isAdmin() || $user->isGA() || $user->isMTC()) {
            $request->validate([
                'status' => 'required|in:onprogress,schedule,request_to_close,closed,rejected',
                'mtc_pic_name' => 'nullable|string',
                'ga_notes' => 'nullable|string',
                'after_photo' => 'nullable|image|max:10048',
            ]);

            $vendorDetails = [];
            if ($request->has('assigned_types') && in_array('external', $request->assigned_types)) {
                $vendorNames = $request->input('vendor_name', []);
                $vendorStatuses = $request->input('vendor_status', []);
                foreach ($vendorNames as $i => $name) {
                    if (!empty($name)) {
                        $vendorDetails[] = ['name' => $name, 'status' => $vendorStatuses[$i] ?? null];
                    }
                }
            }

            $assignedTo = $this->compileAssignedToFromRequest($request);

            $updateData = [
                'status' => $request->status,
                'ga_notes' => $request->ga_notes,
                'assigned_to' => $assignedTo,
                'assigned_types' => $request->assigned_types ?? [],
                'internal_types' => $request->internal_types ?? [],
                'mtc_pic_name' => $request->mtc_pic_name,
                'mtc_status' => $request->mtc_status,
                'vendor_details' => $vendorDetails,
            ];

            // LOGIKA MONITORING BOARD
            if ($request->status == 'onprogress') {
                $updateData['assigned_at'] = now();
                $updateData['assigned_by'] = $user->name;

                // Tutup aktivitas lama teknisi yang sedang jalan
                TechnicianActivity::where('user_id', $user->id)
                    ->where('status', 'running')
                    ->get()
                    ->each(fn($activity) => $activity->complete(now()));

                // Buka aktivitas baru kategori Breakdown
                TechnicianActivity::create([
                    'user_id' => $user->id,
                    'category' => 'Breakdown',
                    'reference_id' => $ticket->id,
                    'description' => 'Perbaikan Tiket: ' . $ticket->subject,
                    'start_time' => now(),
                    'status' => 'running'
                ]);
            } else {
                // Jika status pindah dari onprogress ke status lain, tutup aktivitas di Monitoring Board
                TechnicianActivity::where('user_id', $user->id)
                    ->where('category', 'Breakdown')
                    ->where('reference_id', $ticket->id)
                    ->whereIn('status', ['running', 'paused'])
                    ->get()
                    ->each(function ($activity) {
                        if ($activity->status === 'paused' && $activity->paused_at) {
                            $activity->complete($activity->paused_at);
                            return;
                        }

                        $activity->complete(now());
                    });
            }

            if ($request->hasFile('after_photo')) {
                $updateData['after_photo'] = $request->file('after_photo')->store('after_photos', 'public');
            }

            $ticket->update($updateData);
            return redirect()->route('tickets.index')->with('success', 'Ticket berhasil diupdate!');
        }

        // Requester Approval
        if ($user->isUser() && $ticket->status == 'request_to_close') {
            // Validasi akses: hanya requester atau user dari departemen yang sama
            $hasAccess = false;
            if ($ticket->requester_id === $user->id) {
                $hasAccess = true;
            } elseif ($user->department === 'produksi' && $ticket->requester->department === 'produksi') {
                $hasAccess = true;
            }

            if (!$hasAccess) {
                return redirect()->route('tickets.index')->with('error', 'Anda tidak memiliki akses untuk aksi ini!');
            }

            $request->validate([
                'action' => 'required|in:approve,reject',
                'user_notes' => 'nullable|string',
            ]);

            if ($request->action === 'approve') {
                $ticket->update([
                    'status' => 'closed',
                    'user_notes' => $request->user_notes,
                    'closed_date' => now(),
                    'ga_notes' => $ticket->ga_notes . "\n\nUser Approval: " . ($request->user_notes ?: 'Disetujui oleh user')
                ]);
            } else {
                $ticket->update([
                    'status' => 'rejected',
                    'user_notes' => $request->user_notes,
                    'rejected_date' => now(),
                    'ga_notes' => $ticket->ga_notes . "\n\nUser Rejection: " . ($request->user_notes ?: 'Ditolak oleh user')
                ]);
            }

            return redirect()->route('tickets.index')->with('success', 'Respon berhasil disimpan.');
        }

        return redirect()->route('tickets.index')->with('error', 'Aksi tidak diizinkan!');
    }

    private function compileAssignedToFromRequest($request)
    {
        $assignedData = [];
        if ($request->assigned_types && in_array('internal', $request->assigned_types)) {
            if ($request->mtc_pic_name) {
                $assignedData[] = "MTC: {$request->mtc_pic_name}";
            }
        }
        if ($request->assigned_types && in_array('external', $request->assigned_types)) {
            $vendorNames = $request->input('vendor_name', []);
            foreach ($vendorNames as $i => $name) {
                if (!empty($name)) $assignedData[] = "Vendor: " . $name;
            }
        }
        return implode(' | ', $assignedData);
    }

    public function uploadAfterPhoto(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);
        $request->validate(['after_photo' => 'required|image|max:2048']);

        if ($request->hasFile('after_photo')) {
            $file = $request->file('after_photo');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('attachments'), $filename);
            $ticket->after_photo = 'attachments/' . $filename;
            $ticket->save();
        }
        return redirect()->back()->with('success', 'Foto berhasil diupload.');
    }

    public function destroy(Ticket $ticket)
    {
        if (!Auth::user()->isAdmin()) {
            return redirect()->route('tickets.index')->with('error', 'Akses ditolak!');
        }
        $ticket->delete();
        return redirect()->route('tickets.index')->with('success', "Ticket berhasil dihapus!");
    }

    /**
     * Update basic ticket info (Asset, Subject, Description)
     * Hanya untuk admin dengan ID = 1
     */
    public function updateBasic(Request $request, Ticket $ticket)
    {
        // Validasi: Hanya admin dengan ID = 1 yang bisa akses
        if (Auth::user()->id !== 1 || !Auth::user()->isAdmin()) {
            return redirect()->route('tickets.show', $ticket)->with('error', 'Akses ditolak! Hanya admin utama yang dapat mengedit data tiket.');
        }

        // Validasi input
        $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        // Update tiket
        $ticket->update([
            'asset_id' => $request->asset_id,
            'subject' => $request->subject,
            'description' => $request->description,
        ]);

        return redirect()->route('tickets.show', $ticket)->with('success', 'Tiket berhasil diperbarui!');
    }

        // Tambahkan fungsi-fungsi ini di dalam TicketController

public function startWork(Request $request, Ticket $ticket)
{
    $status = 'onprogress';
    $notes = "Teknisi mulai mengerjakan tiket ini.";

    // Data update dasar
    $updateData = ['status' => $status];

    if ($request->has('planned_date')) {
        $updateData['planned_date'] = $request->planned_date;
        $notes = "Pengerjaan dijadwalkan pada tanggal: " . $request->planned_date;
    }

    // PENTING: Set data assignment agar fungsi MarkAsAssigned bisa membaca namanya
    if (Auth::user()->isMTC()) {
        $updateData['assigned_types'] = ['internal'];
        $updateData['internal_types'] = ['mtc'];
        $updateData['mtc_pic_name'] = Auth::user()->name; // Isi nama PIC MTC
    } elseif (Auth::user()->isGA()) {
        $updateData['assigned_types'] = ['internal'];
        $updateData['internal_types'] = ['ga'];
        $updateData['ga_pic_name'] = Auth::user()->name; // Isi nama PIC GA
    }

    $ticket->update($updateData);

    // Tutup aktivitas lama teknisi yang sedang jalan (jika ada)
    TechnicianActivity::where('user_id', Auth::id())
        ->where('status', 'running')
        ->get()
        ->each(fn($activity) => $activity->complete(now()));

    // Integrasi Monitoring Board tetap jalan kawan
    TechnicianActivity::create([
        'user_id' => Auth::id(),
        'category' => 'Breakdown',
        'reference_id' => $ticket->id,
        'description' => 'Perbaikan: ' . $ticket->subject,
        'start_time' => now(),
        'status' => 'running'
    ]);

    $ticket->notes()->create([
        'user_id' => Auth::id(),
        'note' => $notes
    ]);

    // Sekarang fungsi ini akan menghasilkan string "MTC: Nama Anda" kawan
    $ticket->markAsAssigned(Auth::user()->name);
    
    return back()->with('success', 'Status berubah menjadi On Progress.');
}

public function markAsFinished(Request $request, Ticket $ticket)
{
    $request->validate([
        'after_photo' => 'nullable|image|max:10048',
        'problem_cause' => 'required|string',
        'serah_terima_teknisi' => 'nullable|string',
        'serah_terima_user' => 'nullable|string'
    ]);

    if ($request->hasFile('after_photo')) {
        $file = $request->file('after_photo');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $file->move(public_path('attachments'), $fileName);
        $ticket->after_photo = 'attachments/' . $fileName;
    }

    $ticket->status = 'request_to_close';
    $ticket->save();

    // Tutup aktivitas di Monitoring Board
    TechnicianActivity::where('user_id', Auth::id())
        ->where('reference_id', $ticket->id)
        ->whereIn('status', ['running', 'paused'])
        ->get()
        ->each(function ($activity) {
            if ($activity->status === 'paused' && $activity->paused_at) {
                $activity->complete($activity->paused_at);
                return;
            }

            $activity->complete(now());
        });

        $ticket->update([
        'status' => 'request_to_close',
        'problem_cause' => $request->problem_cause,
        'ga_notes' => $request->closing_note,
        'serah_terima_teknisi' => $request->serah_terima_teknisi,
        'serah_terima_user' => $request->serah_terima_user
    ]);

    $ticket->notes()->create([
        'user_id' => Auth::id(),
        'note' => "Pekerjaan Selesai.\nPenyebab: " . $request->problem_cause . "\nCatatan: " . $request->closing_note
    ]);

    return back()->with('success', 'Tiket diajukan untuk ditutup.');
}

public function closeTicket(Ticket $ticket)
{
    $user = Auth::user();

    // Hanya requester yang bisa close
    if ($user->id !== $ticket->requester_id) {
        // Kecuali jika user dari departemen produksi dan tiket juga dari produksi
        if (!($user->department === 'produksi' && $ticket->requester->department === 'produksi')) {
            abort(403, 'Hanya pembuat tiket atau user dari departemen yang sama yang bisa menutup tiket ini.');
        }
    }

    $ticket->update(['status' => 'closed', 'closed_date' => now()]);

    $ticket->notes()->create([
        'user_id' => Auth::id(),
        'note' => "Tiket telah ditutup dan dikonfirmasi Selesai."
    ]);

    return back()->with('success', 'Tiket berhasil ditutup.');
}

public function addNote(Request $request, Ticket $ticket)
{
    $request->validate(['note' => 'required|string']);

    $ticket->notes()->create([
        'user_id' => Auth::id(),
        'note' => $request->note
    ]);

    return back()->with('success', 'Catatan ditambahkan.');
}

    // 1. Teknisi klik "Pengerjaan Pending"
public function setPending(Request $request, Ticket $ticket) {
    $request->validate(['reason' => 'required|string']);

    $pendingTime = now();
    $ticket->update(['status' => 'pending']);

    $currentActivity = TechnicianActivity::where('user_id', Auth::id())
        ->where('category', 'Breakdown')
        ->where('reference_id', $ticket->id)
        ->where('status', 'running')
        ->latest('start_time')
        ->first();

    if ($currentActivity) {
        $currentActivity->pause($request->reason, $pendingTime);
    }
    
    $ticket->notes()->create([
        'user_id' => Auth::id(),
        'note' => "Pengerjaan di-PENDING oleh Teknisi. Alasan: " . $request->reason
    ]);

    return back()->with('success', 'Tiket sekarang berstatus Pending (Menunggu Verifikasi SPV).');
}

// 2. SPV Review Tiket Pending
public function spvReview(Request $request, Ticket $ticket) {
    $action = $request->action;
    $note = '';
    $updateData = [];

    switch ($action) {
        case 're-work':
            // Lanjutkan pengerjaan dengan instruksi SPV
            $updateData['status'] = 'onprogress';
            $note = "INSTRUKSI SPV: " . $request->spv_note;
            break;

        case 'menunggu-sparepart':
            // Menunggu sparepart (dahulu purchasing)
            $updateData['status'] = 'pending';
            $updateData['estimated_date'] = $request->estimated_date;
            $updateData['pr_number'] = $request->pr_number;

            $note = "MENUNGGU SPAREPART: " .
                    "\nNo PR: " . ($request->pr_number ?? '-') .
                    "\nEstimasi tiba: " . ($request->estimated_date ? \Carbon\Carbon::parse($request->estimated_date)->format('d/m/Y') : '-');
            break;

        case 'koordinasi-produksi':
            // Koordinasi dengan produksi
            $updateData['status'] = 'pending';
            $updateData['estimated_date'] = $request->estimated_date;
            $updateData['coordination_notes'] = $request->coordination_notes;

            $note = "KOORDINASI DENGAN PRODUKSI: " .
                    "\nCatatan: " . ($request->coordination_notes ?? '-') .
                    "\nEstimasi waktu: " . ($request->estimated_date ? \Carbon\Carbon::parse($request->estimated_date)->format('d/m/Y') : '-');
            break;

        case 'perbaikan-eksternal':
            // Perbaikan dilakukan pihak eksternal
            $updateData['status'] = 'pending';
            $updateData['estimated_date'] = $request->estimated_date;
            $updateData['external_vendor'] = $request->external_vendor;
            $updateData['external_notes'] = $request->external_notes;

            $note = "PERBAIKAN PIHAK EKSTERNAL: " .
                    "\nVendor: " . ($request->external_vendor ?? '-') .
                    "\nCatatan: " . ($request->external_notes ?? '-') .
                    "\nEstimasi selesai: " . ($request->estimated_date ? \Carbon\Carbon::parse($request->estimated_date)->format('d/m/Y') : '-');
            break;

        default:
            return back()->with('error', 'Tindakan tidak valid kawan.');
    }

    // Update ticket
    $ticket->update($updateData);

    // Tambahkan note
    $ticket->notes()->create([
        'user_id' => Auth::id(),
        'note' => $note
    ]);

    return back()->with('success', 'Verifikasi SPV berhasil disimpan kawan.');
}
public function resumeWork(Ticket $ticket)
{
    $user = Auth::user();
    $resumeTime = now();

    // 1. Tutup aktivitas lama user ini yang masih running (jika ada)
    TechnicianActivity::where('user_id', $user->id)
        ->where('status', 'running')
        ->get()
        ->each(fn($activity) => $activity->complete($resumeTime));

    // 2. Update mtc_pic_name ke teknisi yang melanjutkan
    $ticket->update([
        'status' => 'onprogress',
        'mtc_pic_name' => $user->name,
    ]);

    $pausedActivity = TechnicianActivity::where('category', 'Breakdown')
        ->where('reference_id', $ticket->id)
        ->where('status', 'paused')
        ->latest('paused_at')
        ->first();

    if ($pausedActivity && (int) $pausedActivity->user_id === (int) $user->id) {
        $pausedActivity->resume($resumeTime, [
            'by_user_id' => $user->id,
            'by_user_name' => $user->name,
            'note' => 'Resume tiket setelah pending',
        ]);
    } else {
        if ($pausedActivity && $pausedActivity->paused_at) {
            $pausedActivity->complete($pausedActivity->paused_at);
        }

        TechnicianActivity::create([
            'user_id' => $user->id,
            'category' => 'Breakdown',
            'reference_id' => $ticket->id,
            'description' => 'Melanjutkan Perbaikan: ' . $ticket->subject,
            'start_time' => $resumeTime,
            'resumed_at' => $resumeTime,
            'resumed_from_activity_id' => $pausedActivity?->id,
            'pause_resume_log' => [[
                'type' => 'resumed',
                'at' => $resumeTime->toDateTimeString(),
                'by_user_id' => $user->id,
                'by_user_name' => $user->name,
                'note' => 'Resume tiket setelah pending',
            ]],
            'status' => 'running'
        ]);
    }

    // 4. Update assigned_to di tiket agar nama teknisi baru muncul
    $ticket->markAsAssigned($user->name);

    $ticket->notes()->create([
        'user_id' => $user->id,
        'note' => "Teknisi " . $user->name . " melanjutkan pengerjaan tiket ini."
    ]);

    return back()->with('success', 'Pekerjaan dilanjutkan kawan!');
}

public function rejectByUser(Request $request, Ticket $ticket)
{
    $user = Auth::user();
    $request->validate(['rejection_reason' => 'required|string']);

    // Hanya requester atau user dari departemen yang sama yang bisa reject
    if ($user->id !== $ticket->requester_id) {
        // Kecuali jika user dari departemen produksi dan tiket juga dari produksi
        if (!($user->department === 'produksi' && $ticket->requester->department === 'produksi')) {
            abort(403, 'Anda tidak memiliki akses untuk menolak tiket ini.');
        }
    }

    $ticket->update([
        'status' => 'onprogress', // Kembali dikerjakan
        'was_rejected' => true,    // Beri tanda merah/peringatan
    ]);

    // Masukkan alasan penolakan ke sistem obrolan
    $ticket->notes()->create([
        'user_id' => Auth::id(),
        'note' => "❌ PERBAIKAN DITOLAK: " . $request->rejection_reason
    ]);

    return back()->with('success', 'Tiket dikembalikan ke teknisi kawan.');
}

public function monitoring(Request $request)
{
    $year = $request->get('year', date('Y'));
    $query = Ticket::with(['requester', 'asset']);
    
    if ($year != 'all') { $query->whereYear('request_date', $year); }
    $allTickets = $query->get();

    // Data Kanban Board
    $data['open'] = $allTickets->where('status', 'open');
    $data['assigned'] = $allTickets->where('status', 'onprogress');
    $data['pending'] = $allTickets->where('status', 'pending');
    $data['request_to_close'] = $allTickets->where('status', 'request_to_close');
    $data['closed'] = $allTickets->where('status', 'closed');

    // Data Need to Follow Up (Tiket dengan estimated_date yang perlu di-follow up)
    $data['needFollowUp'] = $allTickets
        ->filter(fn($t) => $t->estimated_date !== null && in_array($t->status, ['pending', 'onprogress']))
        ->sortBy('estimated_date')
        ->values();

    // Statistik Progress Bar (Apply year filter)
    $techniciansQuery = Ticket::whereNotNull('mtc_pic_name');
    if ($year != 'all') { $techniciansQuery->whereYear('request_date', $year); }

    $technicians = $techniciansQuery
        ->selectRaw('mtc_pic_name, count(*) as total')
        ->groupBy('mtc_pic_name')->get();

    $grandTotal = $technicians->sum('total');
    $data['technicianStats'] = $technicians->map(fn($i) => [
        'name' => $i->mtc_pic_name,
        'count' => $i->total,
        'percentage' => $grandTotal > 0 ? round(($i->total / $grandTotal) * 100) : 0
    ]);

    $data['year'] = $year;
    $data['availableYears'] = Ticket::selectRaw('YEAR(request_date) as year')->distinct()->pluck('year');
    
    return view('tickets.monitoring', $data);
}

}
