<?php

namespace App\Http\Controllers;

use App\Models\PmSchedule;
use App\Models\Asset; // Gunakan Asset
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PmScheduleController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            // Hanya Admin dan MTC yang bisa mengelola Jadwal PM
            if (!Auth::user()->isAdmin() && !Auth::user()->isMTC()) {
                abort(403, 'Akses ditolak.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        // Load relasi asset
        $pmSchedules = PmSchedule::with('asset')
            ->orderByRaw("FIELD(schedule_type, 'daily', 'weekly', 'yearly')")
            ->orderBy('asset_id')
            ->get();
        $scheduleTypes = $this->scheduleTypes();

        return view('pm-schedules.index', compact('pmSchedules', 'scheduleTypes'));
    }

    public function create()
    {
        $machines = Asset::orderBy('fa_code')->get();
        $scheduleTypes = $this->scheduleTypes();

        return view('pm-schedules.create', compact('machines', 'scheduleTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'schedule_type' => 'required|string|max:50',
        ]);

        // Cek double schedule
        $exists = PmSchedule::where('asset_id', $request->asset_id)
            ->where('schedule_type', $request->schedule_type)
            ->first();

        if ($exists) {
            return back()->withErrors(['schedule_type' => 'Jadwal tipe ini sudah ada untuk aset tersebut.'])->withInput();
        }

        $asset = Asset::findOrFail($request->asset_id);
        $typeName = $this->scheduleTypeName($request->schedule_type);
        
        PmSchedule::create([
            'asset_id' => $request->asset_id,
            'schedule_type' => $request->schedule_type,
            'name' => "{$typeName} - {$asset->name}",
            'description' => "Preventive Maintenance untuk {$asset->name}",
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('pm.schedule.index')->with('success', 'Jadwal PM berhasil dibuat.');
    }

    public function show(PmSchedule $pmSchedule)
    {
        $pmSchedule->load(['asset', 'checklistTemplates']);
        return view('pm-schedules.show', compact('pmSchedule'));
    }

    public function edit(PmSchedule $pmSchedule)
    {
        $machines = Asset::orderBy('fa_code')->get();
        $scheduleTypes = $this->scheduleTypes();

        return view('pm-schedules.edit', compact('pmSchedule', 'machines', 'scheduleTypes'));
    }

    public function update(Request $request, PmSchedule $pmSchedule)
    {
        $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'schedule_type' => 'required|string|max:50',
        ]);

        $asset = Asset::findOrFail($request->asset_id);
        $typeName = $this->scheduleTypeName($request->schedule_type);

        $pmSchedule->update([
            'asset_id' => $request->asset_id,
            'schedule_type' => $request->schedule_type,
            'name' => "{$typeName} - {$asset->name}",
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('pm.schedule.index')->with('success', 'Jadwal PM diperbarui kawan.');
    }

    public function destroy(PmSchedule $pmSchedule)
    {
        $pmSchedule->delete();
        return redirect()->route('pm.schedule.index')->with('success', 'Jadwal berhasil dihapus.');
    }

    public function toggleStatus(PmSchedule $pmSchedule)
    {
        $pmSchedule->update(['is_active' => !$pmSchedule->is_active]);
        return back()->with('success', 'Status jadwal berubah.');
    }

    private function scheduleTypes(): array
    {
        return [
            'daily' => 'Harian (Daily Check)',
            'weekly' => 'Mingguan (Weekly Check)',
            'yearly' => 'Tahunan (Yearly Check)',
        ];
    }

    private function scheduleTypeName(string $type): string
    {
        return [
            'daily' => 'Jadwal Harian (Daily)',
            'weekly' => 'Jadwal Rutin (Weekly)',
            'yearly' => 'Jadwal Major (Yearly)',
        ][$type] ?? 'Jadwal PM';
    }
}
