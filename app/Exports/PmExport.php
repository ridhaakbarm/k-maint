<?php

namespace App\Exports;

use App\Models\PmCheck;
use App\Models\PmCheckItem;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class PmExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $weekNumber;
    protected $scheduleType;

    public function __construct($weekNumber = null, $scheduleType = 'weekly')
    {
        $this->weekNumber = $weekNumber ?? Carbon::now()->weekOfYear;
        $this->scheduleType = $scheduleType;
    }

    public function collection()
    {
        // Ambil semua PM checks untuk week yang dipilih
        $pmChecks = PmCheck::with([
            'pmSchedule.asset',
            'checkItems.checklistTemplate',
            'technician'
        ])
        ->where('week_number', $this->weekNumber)
        ->whereHas('pmSchedule', function($q) {
            $q->where('schedule_type', $this->scheduleType);
        })
        ->get();

        return $pmChecks;
    }

    public function headings(): array
    {
        return [
            'Week',
            'Mesin / Aset',
            'FA Code',
            'Teknisi',
            'Tanggal Cek',
            'Shift',
            'Status PM',
            'Total Item',
            'Item Selesai',
            'Progress %',
            'Detail Checklist',
            'Waktu Verifikasi',
            'Verified By'
        ];
    }

    public function map($pmCheck): array
    {
        // Hitung progress
        $totalItems = $pmCheck->checkItems->count();
        $completedItems = $pmCheck->checkItems->whereNotNull('condition')->count();
        $progress = $totalItems > 0 ? round(($completedItems / $totalItems) * 100, 1) : 0;

        // Generate detail checklist
        $checklistDetails = [];
        foreach ($pmCheck->checkItems as $item) {
            $template = $item->checklistTemplate;
            if ($template) {
                $status = $item->condition ?? 'Belum Dicek';
                $checklistDetails[] = ($template->item_name ?? '-') . ': ' . $status;
            }
        }
        $detailString = implode("\n", $checklistDetails);

        // Format dates
        $checkDate = $pmCheck->check_date
            ? (is_string($pmCheck->check_date)
                ? Carbon::parse($pmCheck->check_date)->format('d/m/Y')
                : $pmCheck->check_date->format('d/m/Y'))
            : '-';

        $verifiedAt = $pmCheck->verified_at
            ? (is_string($pmCheck->verified_at)
                ? Carbon::parse($pmCheck->verified_at)->format('d/m/Y H:i')
                : $pmCheck->verified_at->format('d/m/Y H:i'))
            : '-';

        return [
            $pmCheck->week_number,
            $pmCheck->pmSchedule->asset->name ?? '-',
            $pmCheck->pmSchedule->asset->fa_code ?? '-',
            $pmCheck->technician->name ?? $pmCheck->technician_name ?? '-',
            $checkDate,
            $pmCheck->shift ?? '-',
            strtoupper(str_replace('_', ' ', $pmCheck->status)),
            $totalItems,
            $completedItems,
            $progress . '%',
            $detailString,
            $verifiedAt,
            $pmCheck->admin->name ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $rowCount = count($this->collection()) + 1;

        // Border untuk semua sel
        $sheet->getStyle('A1:M' . $rowCount)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ]);

        // Auto wrap text
        $sheet->getStyle('B:B')->getAlignment()->setWrapText(true); // Mesin
        $sheet->getStyle('L:L')->getAlignment()->setWrapText(true); // Detail Checklist

        // Bold header
        $sheet->getStyle('A1:M1')->getFont()->setBold(true);

        // Center header text
        $sheet->getStyle('A1:M1')->getAlignment()->setHorizontal('center');

        // Warna latar belakang header
        $sheet->getStyle('A1:M1')->getFill()->applyFromArray([
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'color' => ['argb' => 'FFE0E0E0'],
        ]);

        // Set tinggi row header
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Center alignment untuk kolom numerik
        $sheet->getStyle('H:K')->getAlignment()->setHorizontal('center');

        return [];
    }
}
