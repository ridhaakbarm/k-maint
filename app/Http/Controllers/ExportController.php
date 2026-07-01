<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\PmSchedule;
use App\Exports\TicketsExport;
use App\Exports\PmExport;
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
}
