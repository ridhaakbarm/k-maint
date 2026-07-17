<?php

namespace App\Exports;

use App\Models\ChecklistTemplate;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ChecklistTemplateExport implements FromCollection, WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return ChecklistTemplate::with('pmSchedule.asset')
            ->orderBy('pm_schedule_id')
            ->orderBy('order')
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Aset',
            'Jadwal PM',
            'Urutan',
            'Item Checklist',
            'Bagian yang Dicek',
            'Instruksi',
            'Standar Pengecekan',
            'Minggu Aktif',
            'Status'
        ];
    }

    public function map($template): array
    {
        $activeWeeks = $template->active_weeks ? implode(', ', $template->active_weeks) : '-';
        
        return [
            $template->id,
            $template->pmSchedule->asset->name ?? '-',
            $template->pmSchedule->name ?? '-',
            $template->order,
            $template->item_name,
            $template->checked_part,
            $template->instructions,
            $template->check_standard,
            $activeWeeks,
            $template->is_active ? 'Aktif' : 'Non-Aktif'
        ];
    }
}
