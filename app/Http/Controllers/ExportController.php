<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\PmCheckItem;
use App\Models\PmSchedule;
use App\Exports\TicketsExport;
use App\Exports\PmExport;
use App\Exports\ManagerReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Throwable;

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
        $timeout = (int) config('exports.pm_timeout', 1800);
        @set_time_limit($timeout);
        @ini_set('max_execution_time', (string) $timeout);

        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $validated['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate = $validated['end_date'] ?? now()->endOfMonth()->toDateString();

        if ($request->input('format', 'csv') !== 'xlsx') {
            return $this->exportPmCsv($startDate, $endDate);
        }

        // Generate filename
        $filename = 'pm_export_' . $startDate . '_sd_' . $endDate . '_' . date('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new PmExport($startDate, $endDate), $filename);
    }

    public function startPmXlsxExport(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $validated['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate = $validated['end_date'] ?? now()->endOfMonth()->toDateString();
        $token = (string) Str::uuid();
        $filename = 'pm_export_' . $startDate . '_sd_' . $endDate . '_' . date('Y-m-d_H-i-s') . '.xlsx';
        $directory = 'exports/pm/' . $token;
        $filePath = $directory . '/' . $filename;
        $statusPath = $directory . '/status.json';

        Storage::makeDirectory($directory);
        $this->writePmExportStatus($statusPath, [
            'status' => 'processing',
            'message' => 'File XLSX sedang dibuat.',
            'filename' => $filename,
            'download_url' => null,
            'created_at' => now()->toDateTimeString(),
            'finished_at' => null,
        ]);

        app()->terminating(function () use ($startDate, $endDate, $filePath, $statusPath, $filename, $token) {
            $timeout = (int) config('exports.pm_timeout', 1800);
            @set_time_limit($timeout);
            @ini_set('max_execution_time', (string) $timeout);

            try {
                Excel::store(new PmExport($startDate, $endDate), $filePath, 'local');

                $this->writePmExportStatus($statusPath, [
                    'status' => 'done',
                    'message' => 'File XLSX sudah siap diunduh.',
                    'filename' => $filename,
                    'download_url' => route('export.pm.xlsx.download', ['token' => $token]),
                    'finished_at' => now()->toDateTimeString(),
                ]);
            } catch (Throwable $exception) {
                $this->writePmExportStatus($statusPath, [
                    'status' => 'failed',
                    'message' => config('app.debug')
                        ? $exception->getMessage()
                        : 'Export gagal diproses. Silakan coba periode yang lebih pendek atau hubungi admin.',
                    'filename' => $filename,
                    'download_url' => null,
                    'finished_at' => now()->toDateTimeString(),
                ]);
            }
        });

        return view('exports.pm_xlsx_status', [
            'statusUrl' => route('export.pm.xlsx.status', ['token' => $token]),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'filename' => $filename,
        ]);
    }

    public function pmXlsxExportStatus(string $token)
    {
        abort_unless(Str::isUuid($token), 404);

        $statusPath = 'exports/pm/' . $token . '/status.json';
        abort_unless(Storage::exists($statusPath), 404);

        return response()->json($this->readPmExportStatus($statusPath));
    }

    public function downloadPmXlsxExport(string $token)
    {
        abort_unless(Str::isUuid($token), 404);

        $directory = 'exports/pm/' . $token;
        $statusPath = $directory . '/status.json';
        abort_unless(Storage::exists($statusPath), 404);

        $status = $this->readPmExportStatus($statusPath);
        abort_unless(($status['status'] ?? null) === 'done', 404);

        $filePath = $directory . '/' . ($status['filename'] ?? '');
        abort_unless(Storage::exists($filePath), 404);

        return response()->download(storage_path('app/' . $filePath), $status['filename']);
    }

    protected function writePmExportStatus(string $path, array $status): void
    {
        $current = Storage::exists($path) ? $this->readPmExportStatus($path) : [];

        Storage::put($path, json_encode(array_merge($current, $status), JSON_PRETTY_PRINT));
    }

    protected function readPmExportStatus(string $path): array
    {
        $status = json_decode(Storage::get($path), true);

        return is_array($status) ? $status : [
            'status' => 'failed',
            'message' => 'Status export tidak bisa dibaca.',
        ];
    }

    protected function exportPmCsv(string $startDate, string $endDate)
    {
        $filename = 'pm_export_' . $startDate . '_sd_' . $endDate . '_' . date('Y-m-d_H-i-s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
            'X-Accel-Buffering' => 'no',
        ];

        return response()->streamDownload(function () use ($startDate, $endDate) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'No PM',
                'Week',
                'Tipe Jadwal',
                'Mesin / Aset',
                'Nama Jadwal',
                'Teknisi',
                'Tanggal Cek',
                'Due Date',
                'Shift',
                'Status PM',
                'Total Item',
                'Item Selesai',
                'Progress %',
                'Urutan Item',
                'Item Checklist',
                'Bagian Dicek',
                'Instruksi',
                'Standar Pengecekan',
                'Kondisi',
                'Waktu Dicek',
                'Dicek Oleh',
                'Action Taken',
                'Next Action',
                'Status Follow Up',
                'Catatan Follow Up',
                'Tanggal Eksekusi',
                'Executed By',
                'Verified By',
                'Approved By',
                'Remark',
                'Admin Verifikasi',
            ]);
            self::flushOutput();

            PmCheckItem::query()->with([
                'checklistTemplate',
                'checkedBy',
                'verifiedBy',
                'pmCheck' => function ($query) {
                    $query->withCount([
                        'checkItems as export_total_items_count',
                        'checkItems as export_completed_items_count' => fn($items) => $items
                            ->whereNotNull('condition')
                            ->where('condition', '!=', ''),
                    ]);
                },
                'pmCheck.pmSchedule.asset',
                'pmCheck.technician',
                'pmCheck.admin',
            ])->whereHas('pmCheck', function ($q) use ($startDate, $endDate) {
                $q->where(function ($dateQuery) use ($startDate, $endDate) {
                    $dateQuery->whereBetween('check_date', [$startDate, $endDate])
                        ->orWhere(function ($fallbackQuery) use ($startDate, $endDate) {
                            $fallbackQuery->whereNull('check_date')
                                ->whereBetween('due_date', [$startDate, $endDate]);
                        });
                });
            })
                ->join('pm_checks', 'pm_check_items.pm_check_id', '=', 'pm_checks.id')
                ->leftJoin('checklist_templates', 'pm_check_items.checklist_template_id', '=', 'checklist_templates.id')
                ->orderBy('pm_checks.check_date')
                ->orderBy('pm_checks.due_date')
                ->orderBy('pm_checks.week_number')
                ->orderBy('checklist_templates.order')
                ->select('pm_check_items.*')
                ->chunk(500, function ($items) use ($handle) {
                    foreach ($items as $item) {
                        $pmCheck = $item->pmCheck;
                        $schedule = $pmCheck?->pmSchedule;
                        $template = $item->checklistTemplate;
                        $totalItems = $pmCheck->export_total_items_count ?? 0;
                        $completedItems = $pmCheck->export_completed_items_count ?? 0;
                        $progress = $totalItems > 0 ? round(($completedItems / $totalItems) * 100, 1) : 0;

                        fputcsv($handle, [
                            $pmCheck->id ?? '-',
                            $pmCheck->week_number ?? '-',
                            self::scheduleTypeLabel($template->frequency_label ?? $schedule?->schedule_type ?? null),
                            $schedule?->asset?->name ?? '-',
                            $schedule?->name ?? '-',
                            $pmCheck?->technician?->name ?? $pmCheck?->technician_name ?? '-',
                            self::formatDate($pmCheck?->check_date ?? null),
                            self::formatDate($pmCheck?->due_date ?? null),
                            $pmCheck?->shift ?? '-',
                            self::statusLabel($pmCheck?->status ?? null),
                            $totalItems,
                            $completedItems,
                            $progress . '%',
                            $template?->order ?? '-',
                            $template?->item_name ?? '-',
                            $template?->checked_part ?? '-',
                            $template?->instructions ?? '-',
                            $template?->check_standard ?? '-',
                            $item->condition ?? 'Belum Dicek',
                            self::formatDate($item->checked_at ?? null, 'd/m/Y H:i'),
                            $item->checkedBy?->name ?? '-',
                            $item->action_taken ?? '-',
                            $item->next_action ?? '-',
                            $item->follow_up_status ?? '-',
                            $item->follow_up_note ?? '-',
                            self::formatDate($item->execution_date ?? null),
                            $item->executed_by ?? '-',
                            $item->verified_by ?? $item->verifiedBy?->name ?? '-',
                            $item->approved_by ?? '-',
                            $item->remark ?? '-',
                            $pmCheck?->admin?->name ?? '-',
                        ]);
                    }

                    fflush($handle);
                    self::flushOutput();
                });

            fclose($handle);
        }, $filename, $headers);
    }

    protected static function flushOutput(): void
    {
        if (ob_get_level() > 0) {
            @ob_flush();
        }

        flush();
    }

    protected static function formatDate($value, string $format = 'd/m/Y'): string
    {
        if (!$value) {
            return '-';
        }

        return $value instanceof Carbon
            ? $value->format($format)
            : Carbon::parse($value)->format($format);
    }

    protected static function scheduleTypeLabel(?string $type): string
    {
        return [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'bi-weekly' => 'Bi-Weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly',
        ][$type] ?? ($type ? ucfirst($type) : '-');
    }

    protected static function statusLabel(?string $status): string
    {
        return $status ? strtoupper(str_replace('_', ' ', $status)) : '-';
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
