<?php

namespace App\Exports;

use App\Models\PmCheckItem;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TechnicianPmItemsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $startDate;
    protected $endDate;
    protected $technicianId;

    public function __construct($startDate, $endDate, $technicianId)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->technicianId = $technicianId;
    }

    public function collection()
    {
        return PmCheckItem::with(['pmCheck.pmSchedule.asset', 'checklistTemplate'])
            ->where('checked_by_user_id', $this->technicianId)
            ->whereHas('pmCheck', function ($query) {
                $query->whereBetween('check_date', [$this->startDate, $this->endDate])
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('check_date')
                            ->whereBetween('due_date', [$this->startDate, $this->endDate]);
                    });
            })
            ->get();
    }

    public function headings(): array
    {
        return [
            'Tanggal Dikerjakan',
            'Nama Mesin (Asset)',
            'Jadwal PM',
            'Tugas (Item)',
            'Kondisi',
            'Tindakan',
            'Teknisi',
        ];
    }

    public function map($item): array
    {
        return [
            $item->checked_at ? $item->checked_at->format('d/m/Y H:i') : '',
            $item->pmCheck->pmSchedule->asset->name ?? '-',
            $item->pmCheck->pmSchedule->name ?? '-',
            $item->checklistTemplate->task ?? '-',
            $item->condition ?? '-',
            $item->action_taken ?? '-',
            $item->checkedBy->name ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}