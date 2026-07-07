<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\InternalTicket;
use App\Models\PmCheckItem;
use App\Models\TechnicianActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InternalTicketController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeInternalTicketAccess();

        $query = InternalTicket::with(['requester', 'asset', 'pmCheckItem.pmCheck.pmSchedule.asset']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('source_type')) {
            $query->where('source_type', $request->source_type);
        }

        if ($request->filled('year') && $request->year !== 'all') {
            $query->whereYear('request_date', $request->year);
        }

        $tickets = $query->latest()->get();

        return view('internal-tickets.index', compact('tickets'));
    }

    public function create(Request $request)
    {
        $this->authorizeInternalTicketAccess();

        $generatedTicketNo = InternalTicket::generateTicketNo();
        $assets = Asset::orderBy('name')->get();
        $pmCheckItem = null;

        if ($request->filled('pm_check_item_id')) {
            $pmCheckItem = PmCheckItem::with(['pmCheck.pmSchedule.asset', 'checklistTemplate'])
                ->find($request->pm_check_item_id);
        }

        return view('internal-tickets.create', compact('generatedTicketNo', 'assets', 'pmCheckItem'));
    }

    public function store(Request $request)
    {
        $this->authorizeInternalTicketAccess();

        $request->validate([
            'ticket_no' => 'required|string|unique:internal_tickets,ticket_no',
            'asset_id' => 'nullable|exists:assets,id',
            'pm_check_item_id' => 'nullable|exists:pm_check_items,id',
            'source_type' => 'required|in:pm,lisan,temuan_lain',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'assigned_to_name' => 'nullable|string|max:255',
            'target_date' => 'nullable|date',
            'priority' => 'required|in:low,normal,high,urgent',
            'attachment' => 'nullable|image|max:10048',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('attachments/internal_tickets'), $fileName);
            $attachmentPath = 'attachments/internal_tickets/' . $fileName;
        }

        $ticket = InternalTicket::create([
            'ticket_no' => $request->ticket_no,
            'request_date' => now(),
            'requester_id' => Auth::id(),
            'asset_id' => $request->asset_id,
            'pm_check_item_id' => $request->pm_check_item_id,
            'source_type' => $request->source_type,
            'subject' => $request->subject,
            'description' => $request->description,
            'assigned_to_name' => $request->assigned_to_name,
            'target_date' => $request->target_date,
            'priority' => $request->priority,
            'attachment' => $attachmentPath,
            'status' => 'open',
        ]);

        $ticket->notes()->create([
            'user_id' => Auth::id(),
            'note' => 'Tiket internal dibuat.',
        ]);

        return redirect()->route('internal-tickets.show', $ticket)
            ->with('success', 'Tiket internal ' . $ticket->ticket_no . ' berhasil dibuat.');
    }

    public function show(InternalTicket $internalTicket)
    {
        $this->authorizeInternalTicketAccess();

        $internalTicket->load([
            'requester',
            'asset',
            'pmCheckItem.pmCheck.pmSchedule.asset',
            'pmCheckItem.checklistTemplate',
            'notes.user',
        ]);

        return view('internal-tickets.show', ['ticket' => $internalTicket]);
    }

    public function startWork(InternalTicket $internalTicket)
    {
        $this->authorizeInternalTicketAccess();

        if (!in_array($internalTicket->status, ['open', 'pending'], true)) {
            return back()->with('error', 'Tiket ini tidak bisa dimulai dari status saat ini.');
        }

        TechnicianActivity::where('user_id', Auth::id())
            ->where('status', 'running')
            ->get()
            ->each(fn ($activity) => $activity->complete(now()));

        $internalTicket->update([
            'status' => 'onprogress',
            'started_at' => $internalTicket->started_at ?? now(),
            'assigned_to_name' => $internalTicket->assigned_to_name ?: Auth::user()->name,
        ]);

        TechnicianActivity::create([
            'user_id' => Auth::id(),
            'category' => 'Lain-lain',
            'reference_id' => $internalTicket->id,
            'description' => 'Tiket Internal: ' . $internalTicket->subject,
            'start_time' => now(),
            'status' => 'running',
        ]);

        $internalTicket->notes()->create([
            'user_id' => Auth::id(),
            'note' => 'Pengerjaan dimulai oleh ' . Auth::user()->name . '.',
        ]);

        return back()->with('success', 'Pengerjaan tiket internal dimulai.');
    }

    public function updateProgress(Request $request, InternalTicket $internalTicket)
    {
        $this->authorizeInternalTicketAccess();

        $request->validate([
            'status' => 'required|in:onprogress,pending',
            'note' => 'required|string',
            'work_result' => 'nullable|string',
            'target_date' => 'nullable|date',
            'assigned_to_name' => 'nullable|string|max:255',
        ]);

        $internalTicket->update([
            'status' => $request->status,
            'work_result' => $request->work_result ?: $internalTicket->work_result,
            'target_date' => $request->target_date ?: $internalTicket->target_date,
            'assigned_to_name' => $request->assigned_to_name ?: $internalTicket->assigned_to_name,
        ]);

        $activity = TechnicianActivity::where('user_id', Auth::id())
            ->where('category', 'Lain-lain')
            ->where('reference_id', $internalTicket->id)
            ->whereIn('status', ['running', 'paused'])
            ->latest('start_time')
            ->first();

        if ($activity && $request->status === 'pending' && $activity->status === 'running') {
            $activity->pause($request->note);
        }

        if ($activity && $request->status === 'onprogress' && $activity->status === 'paused') {
            $activity->resume(now(), [
                'by_user_id' => Auth::id(),
                'by_user_name' => Auth::user()->name,
                'note' => $request->note,
            ]);
        }

        $internalTicket->notes()->create([
            'user_id' => Auth::id(),
            'note' => $request->note,
        ]);

        return back()->with('success', 'Progress tiket internal berhasil diperbarui.');
    }

    public function close(Request $request, InternalTicket $internalTicket)
    {
        $this->authorizeInternalTicketAccess();

        $request->validate([
            'work_result' => 'required|string',
            'after_photo' => 'nullable|image|max:10048',
        ]);

        $afterPhotoPath = $internalTicket->after_photo;
        if ($request->hasFile('after_photo')) {
            $file = $request->file('after_photo');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('attachments/internal_tickets'), $fileName);
            $afterPhotoPath = 'attachments/internal_tickets/' . $fileName;
        }

        $internalTicket->update([
            'status' => 'closed',
            'work_result' => $request->work_result,
            'after_photo' => $afterPhotoPath,
            'closed_at' => now(),
        ]);

        TechnicianActivity::where('user_id', Auth::id())
            ->where('category', 'Lain-lain')
            ->where('reference_id', $internalTicket->id)
            ->whereIn('status', ['running', 'paused'])
            ->get()
            ->each(function ($activity) {
                $activity->complete($activity->status === 'paused' && $activity->paused_at ? $activity->paused_at : now());
            });

        $internalTicket->notes()->create([
            'user_id' => Auth::id(),
            'note' => "Tiket internal ditutup.\nHasil pekerjaan: " . $request->work_result,
        ]);

        return redirect()->route('internal-tickets.show', $internalTicket)
            ->with('success', 'Tiket internal berhasil ditutup.');
    }

    public function addNote(Request $request, InternalTicket $internalTicket)
    {
        $this->authorizeInternalTicketAccess();

        $request->validate(['note' => 'required|string']);

        $internalTicket->notes()->create([
            'user_id' => Auth::id(),
            'note' => $request->note,
        ]);

        return back()->with('success', 'Catatan berhasil ditambahkan.');
    }

    public function destroy(InternalTicket $internalTicket)
    {
        if (!Auth::user()->isAdmin()) {
            return redirect()->route('internal-tickets.index')->with('error', 'Akses ditolak.');
        }

        $internalTicket->delete();

        return redirect()->route('internal-tickets.index')->with('success', 'Tiket internal berhasil dihapus.');
    }

    private function authorizeInternalTicketAccess(): void
    {
        $user = Auth::user();
        $department = strtolower(trim((string) $user->department));

        if ($user->isAdmin() || $user->isMTC() || in_array($department, ['maintenance', 'engineering', 'mtc'], true)) {
            return;
        }

        abort(403, 'Hanya admin atau departemen Maintenance/Engineering yang dapat mengakses tiket internal.');
    }
}
