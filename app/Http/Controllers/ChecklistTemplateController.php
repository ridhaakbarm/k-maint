<?php

namespace App\Http\Controllers;

use App\Models\ChecklistTemplate;
use App\Models\PmSchedule;
use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Imports\ChecklistImport;
use App\Exports\ChecklistTemplateExport;
use Maatwebsite\Excel\Facades\Excel;

class ChecklistTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            
            if (!$user->isAdmin() && !$user->isMTC()) {
                abort(403, 'Akses ditolak. Hanya Admin atau MTC yang diperbolehkan.');
            }

            $restrictedMethods = ['create', 'store', 'edit', 'update', 'destroy', 'import', 'toggleStatus'];
            if (in_array($request->route()->getActionMethod(), $restrictedMethods)) {
                if ($user->username !== 'andre') {
                    abort(403, 'Akses ditolak. Hanya akun andre yang dapat mengedit template.');
                }
            }

            return $next($request);
        });
    }

    public function index()
    {
        $checklistTemplates = ChecklistTemplate::with('pmSchedule.asset')
            ->orderBy('pm_schedule_id')
            ->orderBy('order')
            ->get();
            
        return view('checklist-templates.index', compact('checklistTemplates'));
    }

    public function create(Request $request)
    {
        // Mendukung parameter pm_schedule_id dari link "Tambah Manual"
        $preselected_schedule = $request->query('pm_schedule_id');
        
        $pmSchedules = PmSchedule::with(['asset', 'checklistTemplates:id,pm_schedule_id,order'])
            ->where('is_active', true)
            ->get();

        $nextOrders = $pmSchedules->mapWithKeys(function ($schedule) {
            $lastOrder = $schedule->checklistTemplates->max('order');
            return [$schedule->id => ($lastOrder ?? -1) + 1];
        });
            
        return view('checklist-templates.create', compact('pmSchedules', 'preselected_schedule', 'nextOrders'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'pm_schedule_id' => 'required|exists:pm_schedules,id',
            'item_name' => 'required|string|max:255',
            'checked_part' => 'required|string|max:255',
            'instructions' => 'required|string',
            'check_standard' => 'required|string',
            'order' => 'required|integer|min:0',
            'active_weeks' => 'nullable|array',
        ]);

        $pmSchedule = PmSchedule::findOrFail($request->pm_schedule_id);
        $activeWeeks = $pmSchedule->schedule_type === 'daily'
            ? range(1, 52)
            : $request->input('active_weeks', []);

        if ($pmSchedule->schedule_type !== 'daily' && empty($activeWeeks)) {
            return back()->withErrors(['active_weeks' => 'Pilih minimal satu week untuk template ini.'])->withInput();
        }

        ChecklistTemplate::create([
            'pm_schedule_id' => $request->pm_schedule_id,
            'item_name' => $request->item_name,
            'checked_part' => $request->checked_part,
            'operation_source' => $request->operation_source,
            'instructions' => $request->instructions,
            'check_standard' => $request->check_standard,
            'order' => $request->order,
            'is_active' => $request->has('is_active'),
            'active_weeks' => $activeWeeks, 
        ]);

        return redirect()->route('pm.templates.index')->with('success', 'Item checklist berhasil disimpan.');
    }

    public function show(ChecklistTemplate $checklistTemplate)
    {
        $checklistTemplate->load(['pmSchedule.asset']);
        return view('checklist-templates.show', compact('checklistTemplate'));
    }

    public function edit(ChecklistTemplate $checklistTemplate)
    {
        $pmSchedules = PmSchedule::with('asset')->get();
        return view('checklist-templates.edit', compact('checklistTemplate', 'pmSchedules'));
    }

    public function update(Request $request, ChecklistTemplate $checklistTemplate)
    {
        $request->validate([
            'pm_schedule_id' => 'required|exists:pm_schedules,id',
            'item_name' => 'required|string|max:255',
            'checked_part' => 'required|string|max:255',
            'active_weeks' => 'nullable|array',
        ]);

        $pmSchedule = PmSchedule::findOrFail($request->pm_schedule_id);
        $activeWeeks = $pmSchedule->schedule_type === 'daily'
            ? range(1, 52)
            : $request->input('active_weeks', []);

        if ($pmSchedule->schedule_type !== 'daily' && empty($activeWeeks)) {
            return back()->withErrors(['active_weeks' => 'Pilih minimal satu week untuk template ini.'])->withInput();
        }

        $checklistTemplate->update([
            'pm_schedule_id' => $request->pm_schedule_id,
            'item_name' => $request->item_name,
            'checked_part' => $request->checked_part,
            'operation_source' => $request->operation_source,
            'instructions' => $request->instructions,
            'check_standard' => $request->check_standard,
            'order' => $request->order,
            'is_active' => $request->has('is_active'),
            'active_weeks' => $activeWeeks,
        ]);

        return redirect()->route('pm.templates.index')->with('success', 'Template berhasil diperbarui.');
    }

    public function destroy(ChecklistTemplate $checklistTemplate)
    {
        // Hapus semua PM Check Items yang terkait
        $deletedItems = $checklistTemplate->checkItems()->count();
        if ($deletedItems > 0) {
            $checklistTemplate->checkItems()->delete();
        }

        // Hapus template
        $checklistTemplate->delete();

        $message = 'Template berhasil dihapus.';
        if ($deletedItems > 0) {
            $message .= ' ' . $deletedItems . ' data PM Check terkait juga dihapus.';
        }

        return back()->with('success', $message);
    }
    public function export()
    {
        return Excel::download(new ChecklistTemplateExport, 'checklist_templates.xlsx');
    }

    // METHOD IMPORT YANG MENYEBABKAN ERROR TADI
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
            'pm_schedule_id' => 'required|exists:pm_schedules,id',
        ]);

        try {
            // Kita kirim ID Jadwal ke class Import
            Excel::import(new ChecklistImport($request->pm_schedule_id), $request->file('file'));
            
            return back()->with('success', 'Data checklist berhasil diimport!');
        } catch (\Exception $e) {
            // Jika error, munculkan pesannya agar kita tahu salahnya dimana
            return back()->with('error', 'Gagal import: ' . $e->getMessage());
        }
    }


    public function toggleStatus(ChecklistTemplate $checklistTemplate)
    {
        $checklistTemplate->update(['is_active' => !$checklistTemplate->is_active]);
        return back()->with('success', 'Status berhasil diubah.');
    }
}
