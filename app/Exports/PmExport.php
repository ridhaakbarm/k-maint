<?php

namespace App\Exports;

use App\Models\PmCheck;
use App\Models\PmCheckItem;
use App\Models\PmSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PmExport implements WithMultipleSheets
{
    protected Carbon $startDate;
    protected Carbon $endDate;

    public function __construct($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->startOfMonth();
        $this->endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfMonth();
    }

    public function sheets(): array
    {
        $plannedRows = PmExportData::plannedRows($this->startDate, $this->endDate);

        return [
            new PmActualItemsSheet($this->startDate, $this->endDate),
            new PmPlannedItemsSheet($plannedRows),
            new PmSummarySheet($plannedRows, $this->startDate, $this->endDate),
        ];
    }
}

class PmActualItemsSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    protected Carbon $startDate;
    protected Carbon $endDate;
    protected ?Collection $rows = null;

    public function __construct(Carbon $startDate, Carbon $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function title(): string
    {
        return 'Item Aktual';
    }

    public function collection()
    {
        $query = PmCheckItem::with([
            'checklistTemplate',
            'checkedBy',
            'verifiedBy',
            'pmCheck.checkItems',
            'pmCheck.pmSchedule.asset',
            'pmCheck.technician',
            'pmCheck.admin',
        ])->whereHas('pmCheck', function ($q) {
            $q->where(function ($dateQuery) {
                $dateQuery->whereBetween('check_date', [$this->startDate->toDateString(), $this->endDate->toDateString()])
                    ->orWhere(function ($fallbackQuery) {
                        $fallbackQuery->whereNull('check_date')
                            ->whereBetween('due_date', [$this->startDate->toDateString(), $this->endDate->toDateString()]);
                    });
            });
        })
            ->join('pm_checks', 'pm_check_items.pm_check_id', '=', 'pm_checks.id')
            ->leftJoin('checklist_templates', 'pm_check_items.checklist_template_id', '=', 'checklist_templates.id')
            ->orderBy('pm_checks.check_date')
            ->orderBy('pm_checks.due_date')
            ->orderBy('pm_checks.week_number')
            ->orderBy('checklist_templates.order')
            ->select('pm_check_items.*');

        return $this->rows = $query->get();
    }

    public function headings(): array
    {
        return [
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
        ];
    }

    public function map($item): array
    {
        $pmCheck = $item->pmCheck;
        $schedule = $pmCheck?->pmSchedule;
        $template = $item->checklistTemplate;

        $totalItems = $pmCheck?->checkItems?->count() ?? 0;
        $completedItems = $pmCheck?->checkItems?->whereNotNull('condition')->where('condition', '!=', '')->count() ?? 0;
        $progress = $totalItems > 0 ? round(($completedItems / $totalItems) * 100, 1) : 0;

        return [
            $pmCheck->id ?? '-',
            $pmCheck->week_number ?? '-',
            PmExportData::scheduleTypeLabel($schedule->schedule_type ?? null),
            $schedule->asset->name ?? '-',
            $schedule->name ?? '-',
            $pmCheck->technician->name ?? $pmCheck->technician_name ?? '-',
            PmExportData::formatDate($pmCheck->check_date ?? null),
            PmExportData::formatDate($pmCheck->due_date ?? null),
            $pmCheck->shift ?? '-',
            PmExportData::statusLabel($pmCheck->status ?? null),
            $totalItems,
            $completedItems,
            $progress . '%',
            $template->order ?? '-',
            $template->item_name ?? '-',
            $template->checked_part ?? '-',
            $template->instructions ?? '-',
            $template->check_standard ?? '-',
            $item->condition ?? 'Belum Dicek',
            PmExportData::formatDate($item->checked_at ?? null, 'd/m/Y H:i'),
            $item->checkedBy->name ?? '-',
            $item->action_taken ?? '-',
            $item->next_action ?? '-',
            $item->follow_up_status ?? '-',
            $item->follow_up_note ?? '-',
            PmExportData::formatDate($item->execution_date ?? null),
            $item->executed_by ?? '-',
            $item->verified_by ?? $item->verifiedBy->name ?? '-',
            $item->approved_by ?? '-',
            $item->remark ?? '-',
            $pmCheck->admin->name ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        PmExportData::styleTable($sheet, ($this->rows ? $this->rows->count() : 0) + 1);
        $sheet->getStyle('C:E')->getAlignment()->setWrapText(true);
        $sheet->getStyle('O:AD')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A:C')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('G:H')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('J:N')->getAlignment()->setHorizontal('center');

        return [];
    }
}

class PmPlannedItemsSheet implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected Collection $rows;

    public function __construct(Collection $rows)
    {
        $this->rows = $rows;
    }

    public function title(): string
    {
        return 'Rencana Item PM';
    }

    public function headings(): array
    {
        return [
            'Status Target',
            'Tanggal Target',
            'Week',
            'Tipe Jadwal',
            'Mesin / Aset',
            'Nama Jadwal',
            'ID Schedule',
            'ID Template',
            'Urutan Item',
            'Item Checklist',
            'Bagian Dicek',
            'Instruksi',
            'Standar Pengecekan',
            'No PM Aktual',
            'Status PM Aktual',
            'Teknisi Mulai',
            'Tanggal Cek Aktual',
            'Shift',
            'Kondisi',
            'Waktu Dicek',
            'Dicek Oleh',
            'Action Taken',
            'Next Action',
            'Status Follow Up',
        ];
    }

    public function array(): array
    {
        return $this->rows->map(function (array $row) {
            return [
                $row['target_status'],
                $row['target_date'],
                $row['week_number'],
                $row['schedule_type_label'],
                $row['asset_name'],
                $row['schedule_name'],
                $row['schedule_id'],
                $row['template_id'],
                $row['template_order'],
                $row['item_name'],
                $row['checked_part'],
                $row['instructions'],
                $row['check_standard'],
                $row['pm_check_id'],
                $row['pm_check_status'],
                $row['technician_name'],
                $row['actual_check_date'],
                $row['shift'],
                $row['condition'],
                $row['checked_at'],
                $row['checked_by'],
                $row['action_taken'],
                $row['next_action'],
                $row['follow_up_status'],
            ];
        })->all();
    }

    public function styles(Worksheet $sheet)
    {
        PmExportData::styleTable($sheet, $this->rows->count() + 1);
        $sheet->getStyle('D:F')->getAlignment()->setWrapText(true);
        $sheet->getStyle('J:M')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A:D')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('G:I')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('N:T')->getAlignment()->setHorizontal('center');

        return [];
    }
}

class PmSummarySheet implements FromArray, WithStyles, ShouldAutoSize, WithTitle
{
    protected Collection $plannedRows;
    protected Carbon $startDate;
    protected Carbon $endDate;

    public function __construct(Collection $plannedRows, Carbon $startDate, Carbon $endDate)
    {
        $this->plannedRows = $plannedRows;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function title(): string
    {
        return 'Summary Target';
    }

    public function array(): array
    {
        $done = $this->plannedRows->where('is_done', true)->count();
        $target = $this->plannedRows->count();
        $progress = $target > 0 ? round(($done / $target) * 100, 1) : 0;

        $rows = [
            ['SUMMARY TARGET PM'],
            ['Periode', PmExportData::formatDate($this->startDate) . ' - ' . PmExportData::formatDate($this->endDate)],
            ['Total Terjadwal', $target],
            ['Total Dikerjakan', $done],
            ['Belum Dikerjakan', $target - $done],
            ['Progress (%)', $progress],
            [],
            ['Tipe Jadwal', 'Total Terjadwal', 'Dikerjakan', 'Belum Dikerjakan', 'Progress (%)'],
        ];

        foreach ($this->plannedRows->groupBy('schedule_type_label')->sortKeys() as $type => $items) {
            $typeDone = $items->where('is_done', true)->count();
            $typeTotal = $items->count();

            $rows[] = [
                $type,
                $typeTotal,
                $typeDone,
                $typeTotal - $typeDone,
                $typeTotal > 0 ? round(($typeDone / $typeTotal) * 100, 1) : 0,
            ];
        }

        $rows[] = [];
        $rows[] = ['Mesin / Aset', 'Tipe Jadwal', 'Total Terjadwal', 'Dikerjakan', 'Belum Dikerjakan', 'Progress (%)'];

        foreach ($this->plannedRows->groupBy(fn($row) => $row['asset_name'] . '|' . $row['schedule_type_label'])->sortKeys() as $key => $items) {
            [$assetName, $type] = explode('|', $key);
            $machineDone = $items->where('is_done', true)->count();
            $machineTotal = $items->count();

            $rows[] = [
                $assetName,
                $type,
                $machineTotal,
                $machineDone,
                $machineTotal - $machineDone,
                $machineTotal > 0 ? round(($machineDone / $machineTotal) * 100, 1) : 0,
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $highestColumn = $sheet->getHighestColumn();
        $highestRow = $sheet->getHighestRow();

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A8:E8')->getFont()->setBold(true);
        $sheet->getStyle('A8:E8')->getFill()->applyFromArray([
            'fillType' => Fill::FILL_SOLID,
            'color' => ['argb' => 'FFE0E0E0'],
        ]);

        for ($row = 9; $row <= $highestRow; $row++) {
            if ($sheet->getCell('A' . $row)->getValue() === 'Mesin / Aset') {
                $sheet->getStyle('A' . $row . ':F' . $row)->getFont()->setBold(true);
                $sheet->getStyle('A' . $row . ':F' . $row)->getFill()->applyFromArray([
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['argb' => 'FFE0E0E0'],
                ]);
            }
        }

        $sheet->getStyle('A1:' . $highestColumn . $highestRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFDDDDDD'],
                ],
            ],
        ]);

        return [];
    }
}

class PmExportData
{
    public static function plannedRows(Carbon $startDate, Carbon $endDate): Collection
    {
        $start = $startDate->copy()->startOfDay();
        $end = $endDate->copy()->endOfDay();
        $days = collect(range(0, $start->diffInDays($end)))
            ->map(fn($offset) => $start->copy()->addDays($offset));
        $weeks = $days
            ->map(fn(Carbon $date) => [
                'week' => (int) $date->weekOfYear,
                'target_date' => $date->copy()->startOfWeek(),
            ])
            ->unique('week')
            ->values();

        $schedules = PmSchedule::with(['asset', 'checklistTemplates' => fn($query) => $query->where('is_active', true)->orderBy('order')])
            ->where('is_active', true)
            ->get();

        $scheduleIds = $schedules->pluck('id');
        $checks = PmCheck::with(['checkItems.checkedBy', 'technician'])
            ->whereIn('pm_schedule_id', $scheduleIds)
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('check_date', [$start->toDateString(), $end->toDateString()])
                    ->orWhere(function ($fallback) use ($start, $end) {
                        $fallback->whereNull('check_date')
                            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()]);
                    });
            })
            ->get();

        $dailyChecks = $checks
            ->filter(fn($check) => $check->check_date)
            ->groupBy(fn($check) => $check->pm_schedule_id . '|' . Carbon::parse($check->check_date)->toDateString());
        $weeklyChecks = $checks->groupBy(fn($check) => $check->pm_schedule_id . '|' . $check->week_number);

        $rows = collect();

        foreach ($schedules as $schedule) {
            if ($schedule->schedule_type === 'daily') {
                foreach ($days as $date) {
                    $check = optional($dailyChecks->get($schedule->id . '|' . $date->toDateString()))->first();
                    self::appendTemplateRows($rows, $schedule, $schedule->checklistTemplates, $check, $date, (int) $date->weekOfYear);
                }

                continue;
            }

            foreach ($weeks as $weekData) {
                $weekNumber = $weekData['week'];
                $templates = $schedule->checklistTemplates
                    ->filter(fn($template) => self::templateActiveInWeek($template, $weekNumber))
                    ->values();

                if ($templates->isEmpty()) {
                    continue;
                }

                $check = optional($weeklyChecks->get($schedule->id . '|' . $weekNumber))->first();
                self::appendTemplateRows($rows, $schedule, $templates, $check, $weekData['target_date'], $weekNumber);
            }
        }

        return $rows->sortBy([
            ['target_date_sort', 'asc'],
            ['asset_name', 'asc'],
            ['schedule_type_label', 'asc'],
            ['template_order', 'asc'],
        ])->values();
    }

    protected static function appendTemplateRows(Collection $rows, $schedule, Collection $templates, ?PmCheck $check, Carbon $targetDate, int $weekNumber): void
    {
        $checkItems = $check ? $check->checkItems->keyBy('checklist_template_id') : collect();

        foreach ($templates as $template) {
            $item = $checkItems->get($template->id);
            $isDone = $item && filled($item->condition);

            $rows->push([
                'target_status' => $isDone ? 'Dikerjakan' : ($check ? 'PM dibuat, item belum dicek' : 'Belum dibuat PM'),
                'target_date_sort' => $targetDate->toDateString(),
                'target_date' => $targetDate->format('d/m/Y'),
                'week_number' => $weekNumber,
                'schedule_type_label' => self::scheduleTypeLabel($schedule->schedule_type ?? null),
                'asset_name' => $schedule->asset->name ?? '-',
                'schedule_name' => $schedule->name ?? '-',
                'schedule_id' => $schedule->id,
                'template_id' => $template->id,
                'template_order' => $template->order ?? '-',
                'item_name' => $template->item_name ?? '-',
                'checked_part' => $template->checked_part ?? '-',
                'instructions' => $template->instructions ?? '-',
                'check_standard' => $template->check_standard ?? '-',
                'pm_check_id' => $check->id ?? '-',
                'pm_check_status' => self::statusLabel($check->status ?? null),
                'technician_name' => $check->technician->name ?? $check->technician_name ?? '-',
                'actual_check_date' => self::formatDate($check->check_date ?? null),
                'shift' => $check->shift ?? '-',
                'condition' => $item->condition ?? 'Belum Dicek',
                'checked_at' => self::formatDate($item->checked_at ?? null, 'd/m/Y H:i'),
                'checked_by' => $item->checkedBy->name ?? '-',
                'action_taken' => $item->action_taken ?? '-',
                'next_action' => $item->next_action ?? '-',
                'follow_up_status' => $item->follow_up_status ?? '-',
                'is_done' => (bool) $isDone,
            ]);
        }
    }

    protected static function templateActiveInWeek($template, int $weekNumber): bool
    {
        $activeWeeks = is_array($template->active_weeks)
            ? $template->active_weeks
            : json_decode($template->active_weeks, true);

        return is_array($activeWeeks)
            && (in_array($weekNumber, $activeWeeks) || in_array((string) $weekNumber, $activeWeeks));
    }

    public static function styleTable(Worksheet $sheet, int $rowCount): void
    {
        $lastColumn = $sheet->getHighestColumn();
        $dataRange = 'A1:' . $lastColumn . $rowCount;
        $headerRange = 'A1:' . $lastColumn . '1';

        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ]);

        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal('center');
        $sheet->getStyle($headerRange)->getFill()->applyFromArray([
            'fillType' => Fill::FILL_SOLID,
            'color' => ['argb' => 'FFE0E0E0'],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(25);
        $sheet->getStyle('A1:' . $lastColumn . $rowCount)->getAlignment()->setVertical('top');
    }

    public static function formatDate($value, string $format = 'd/m/Y'): string
    {
        if (!$value) {
            return '-';
        }

        return $value instanceof Carbon
            ? $value->format($format)
            : Carbon::parse($value)->format($format);
    }

    public static function scheduleTypeLabel(?string $type): string
    {
        return [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly',
        ][$type] ?? ($type ? ucfirst($type) : '-');
    }

    public static function statusLabel(?string $status): string
    {
        return $status ? strtoupper(str_replace('_', ' ', $status)) : '-';
    }
}
