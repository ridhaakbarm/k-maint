<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\PmSchedule;
use App\Exports\TicketsExport;
use App\Exports\PmExport;
use App\Exports\ManagerReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class ExportController extends Controller
{

    // Export Tiket ke Excel
    public function exportExcel(Request $request)
    {
        $query = Ticket::with(['requester', 'asset']);

        // Apply filters
        if ($request->has('year') && $request->year != 'all') {
            $query->whereYear('request_date', $request->year);
        }

        if ($request->has('month') && $request->month != 'all') {
            $query->whereMonth('request_date', $request->month);
        }

        if ($request->has('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('category_id') && $request->category_id != 'all') {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('request_date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('request_date', '<=', $request->end_date);
        }

        $tickets = $query->orderBy('request_date', 'desc')->get();

        // Generate filename dengan timestamp
        $filename = 'tickets_export_' . date('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new TicketsExport($tickets), $filename);
    }

    // Export PM ke Excel
        public function exportTechnicianPmItems(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'technician_id' => 'required|exists:users,id',
        ]);

        $tech = \App\Models\User::find($validated['technician_id']);
        $filename = 'Item_PM_' . str_replace(' ', '_', $tech->name) . '_' . $validated['start_date'] . '_sd_' . $validated['end_date'] . '.xlsx';

        return Excel::download(new \App\Exports\TechnicianPmItemsExport($validated['start_date'], $validated['end_date'], $validated['technician_id']), $filename);
    }

    public function exportPm(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $validated['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate = $validated['end_date'] ?? now()->endOfMonth()->toDateString();

        // Generate filename
        $filename = 'pm_export_' . $startDate . '_sd_' . $endDate . '_' . date('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new PmExport($startDate, $endDate), $filename);
    }

    public function exportManagerReport(Request $request)
    {
        $user = $request->user();
        if (!($user->isAdmin() || $user->isManager() || $user->isSPV())) {
            abort(403, 'Akses export laporan efektivitas hanya untuk admin, manager, dan SPV.');
        }

        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'technician_id' => 'nullable',
            'technician' => 'nullable',
        ]);

        $startDate = $validated['start_date']
            ?? $validated['date_from']
            ?? now()->startOfMonth()->toDateString();
        $endDate = $validated['end_date']
            ?? $validated['date_to']
            ?? now()->endOfMonth()->toDateString();
        $technicianId = $validated['technician_id'] ?? $validated['technician'] ?? null;
        $technicianId = in_array($technicianId, ['all', '', null], true) ? null : (int) $technicianId;

        $filename = 'Laporan_Efektivitas_Teknisi_' . $startDate . '_sd_' . $endDate . '.xlsx';

        return Excel::download(new ManagerReportExport($startDate, $endDate, $technicianId), $filename);
    }
}
