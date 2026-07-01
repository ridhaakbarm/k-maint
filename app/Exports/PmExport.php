<?php

namespace App\Exports;

use App\Models\PmCheckItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PmExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected ?Carbon $startDate;
    protected ?Carbon $endDate;
    protected ?Collection $rows = null;

    public function __construct($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : null;
        $this->endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : null;
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
            if ($this->startDate) {
                $q->where(function ($dateQuery) {
                    $dateQuery->whereDate('check_date', '>=', $this->startDate->toDateString())
                        ->orWhere(function ($fallbackQuery) {
                            $fallbackQuery->whereNull('check_date')
                                ->whereDate('due_date', '>=', $this->startDate->toDateString());
                        });
                });
            }

            if ($this->endDate) {
                $q->where(function ($dateQuery) {
                    $dateQuery->whereDate('check_date', '<=', $this->endDate->toDateString())
                        ->orWhere(function ($fallbackQuery) {
                            $fallbackQuery->whereNull('check_date')
                                ->whereDate('due_date', '<=', $this->endDate->toDateString());
                        });
                });
            }
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
        $completedItems = $pmCheck?->checkItems?->whereNotNull('condition')->count() ?? 0;
        $progress = $totalItems > 0 ? round(($completedItems / $totalItems) * 100, 1) : 0;

        return [
            $pmCheck->id ?? '-',
            $pmCheck->week_number ?? '-',
            $this->scheduleTypeLabel($schedule->schedule_type ?? null),
            $schedule->asset->name ?? '-',
            $schedule->name ?? '-',
            $pmCheck->technician->name ?? $pmCheck->technician_name ?? '-',
            $this->formatDate($pmCheck->check_date ?? null),
            $this->formatDate($pmCheck->due_date ?? null),
            $pmCheck->shift ?? '-',
            $this->statusLabel($pmCheck->status ?? null),
            $totalItems,
            $completedItems,
            $progress . '%',
            $template->order ?? '-',
            $template->item_name ?? '-',
            $template->checked_part ?? '-',
            $template->instructions ?? '-',
            $template->check_standard ?? '-',
            $item->condition ?? 'Belum Dicek',
            $this->formatDate($item->checked_at ?? null, 'd/m/Y H:i'),
            $item->checkedBy->name ?? '-',
            $item->action_taken ?? '-',
            $item->next_action ?? '-',
            $item->follow_up_status ?? '-',
            $item->follow_up_note ?? '-',
            $this->formatDate($item->execution_date ?? null),
            $item->executed_by ?? '-',
            $item->verified_by ?? $item->verifiedBy->name ?? '-',
            $item->approved_by ?? '-',
            $item->remark ?? '-',
            $pmCheck->admin->name ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $rowCount = ($this->rows ? $this->rows->count() : 0) + 1;
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
        $sheet->getStyle('C:E')->getAlignment()->setWrapText(true);
        $sheet->getStyle('O:AD')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A:N')->getAlignment()->setVertical('top');
        $sheet->getStyle('O:AE')->getAlignment()->setVertical('top');
        $sheet->getStyle('A:C')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('G:H')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('J:N')->getAlignment()->setHorizontal('center');

        return [];
    }

    private function formatDate($value, string $format = 'd/m/Y'): string
    {
        if (!$value) {
            return '-';
        }

        return $value instanceof Carbon
            ? $value->format($format)
            : Carbon::parse($value)->format($format);
    }

    private function scheduleTypeLabel(?string $type): string
    {
        return [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly',
        ][$type] ?? ($type ? ucfirst($type) : '-');
    }

    private function statusLabel(?string $status): string
    {
        return $status ? strtoupper(str_replace('_', ' ', $status)) : '-';
    }
}
