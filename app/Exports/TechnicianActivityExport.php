<?php

namespace App\Exports;

use App\Models\TechnicianActivity;
use App\Models\Ticket;
use App\Models\PmCheck;
use App\Models\User;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Carbon\Carbon;

class TechnicianActivityExport implements WithMultipleSheets
{
    protected $period;
    protected $dateFrom;
    protected $dateTo;
    protected $technicianId;
    protected $activities;
    protected $technicians;
    protected $technicianSummary;
    protected $overallSummary;

    public function __construct($activities, $technicians, $technicianSummary, $overallSummary, $period, $dateFrom, $dateTo, $technicianId)
    {
        $this->activities = $activities;
        $this->technicians = $technicians;
        $this->technicianSummary = $technicianSummary;
        $this->overallSummary = $overallSummary;
        $this->period = $period;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->technicianId = $technicianId;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        return [
            new TechnicianActivityExport_Sheet1('Ringkasan Eksekutif', $this->overallSummary, $this->technicianSummary, $this->period, $this->dateFrom, $this->dateTo),
            new TechnicianActivityExport_Sheet2('Detail Aktivitas', $this->activities),
            new TechnicianActivityExport_Sheet3('Ringkasan Per Personel', $this->technicianSummary),
            new TechnicianActivityExport_Sheet4('Progress PM', $this->dateFrom, $this->dateTo),
            new TechnicianActivityExport_Sheet5('Breakdown Tickets', $this->dateFrom, $this->dateTo),
            new TechnicianActivityExport_Sheet6('Detail Kehadiran', $this->technicianSummary), // SHEET BARU UNTUK CLOCK IN/OUT KAWAN
        ];
    }
}

// ============================================================
// SHEET 1: RINGKASAN EKSEKUTIF
// ============================================================
class TechnicianActivityExport_Sheet1 implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithStyles, \Maatwebsite\Excel\Concerns\ShouldAutoSize, WithTitle
{
    protected $sheetName;
    protected $overallSummary;
    protected $technicianSummary;
    protected $period;
    protected $dateFrom;
    protected $dateTo;

    public function __construct($sheetName, $overallSummary, $technicianSummary, $period, $dateFrom, $dateTo)
    {
        $this->sheetName = $sheetName;
        $this->overallSummary = $overallSummary;
        $this->technicianSummary = $technicianSummary;
        $this->period = $period;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function title(): string
    {
        return $this->sheetName;
    }

    public function collection()
    {
        $data = collect();

        // Header Informasi
        $data->push(['LAPORAN MONITORING TIM TEKNISI']);
        $data->push(['Periode: ' . ucfirst($this->period) . ' (' . Carbon::parse($this->dateFrom)->format('d/m/Y') . ' - ' . Carbon::parse($this->dateTo)->format('d/m/Y') . ')']);
        $data->push(['Generated: ' . Carbon::now()->format('d/m/Y H:i')]);
        $data->push([]);

        // Statistik Overall
        $data->push(['STATISTIK KESELURUHAN']);
        $data->push(['Total Aktivitas', $this->overallSummary['total_activities']]);
        $data->push(['Total Jam Kerja', $this->overallSummary['total_hours']]);
        $data->push([]);

        // Distribusi Kategori
        $data->push(['DISTRIBUSI KATEGORI AKTIVITAS']);
        $data->push(['Kategori', 'Total Menit', 'Total Jam', 'Persentase']);
        $totalMinutes = $this->overallSummary['total_minutes'] > 0 ? $this->overallSummary['total_minutes'] : 1;
        foreach ($this->overallSummary['by_category'] as $category => $minutes) {
            $data->push([
                $category,
                $minutes,
                round($minutes / 60, 2),
                round(($minutes / $totalMinutes) * 100, 1) . '%'
            ]);
        }
        $data->push([]);

        // Top Performers
        $data->push(['TOP 5 PERFORMER (BERDASARKAN TOTAL JAM AKTIVITAS)']);
        $data->push(['Rank', 'Nama Personel', 'Total Aktivitas', 'Jam Kehadiran', 'Jam Aktivitas', 'PM', 'Breakdown', 'Lainnya', 'Produktivitas']);
        $topTechnicians = $this->technicianSummary->sortByDesc('total_hours')->take(5);
        foreach ($topTechnicians as $index => $summary) {
            $data->push([
                $index + 1,
                $summary['user']->name,
                $summary['total_activities'],
                $summary['net_work_hours'] ?? 0,
                $summary['total_hours'],
                $summary['pm_count'],
                $summary['breakdown_count'],
                $summary['other_count'],
                ($summary['productivity'] ?? 0) . '%'
            ]);
        }
        $data->push([]);

        return $data;
    }

    public function headings(): array
    {
        return [];
    }

    public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
    {
        // Bold title
        $sheet->getStyle('A1:A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A5:A5')->getFont()->setBold(true);
        $sheet->getStyle('A9:A9')->getFont()->setBold(true);
        $sheet->getStyle('A15:A15')->getFont()->setBold(true);

        // Header row style
        $sheet->getStyle('A10:D10')->getFont()->setBold(true);
        $sheet->getStyle('A16:I16')->getFont()->setBold(true);

        // Background color for headers
        $sheet->getStyle('A10:D10')->getFill()->applyFromArray([
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'color' => ['argb' => 'FFE0E0E0'],
        ]);
        $sheet->getStyle('A16:I16')->getFill()->applyFromArray([
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'color' => ['argb' => 'FFE0E0E0'],
        ]);

        return [];
    }
}

// ============================================================
// SHEET 2: DETAIL AKTIVITAS
// ============================================================
class TechnicianActivityExport_Sheet2 implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithMapping, \Maatwebsite\Excel\Concerns\WithStyles, \Maatwebsite\Excel\Concerns\ShouldAutoSize, WithTitle
{
    protected $sheetName;
    protected $activities;

    public function __construct($sheetName, $activities)
    {
        $this->sheetName = $sheetName;
        $this->activities = $activities;
    }

    public function title(): string
    {
        return $this->sheetName;
    }

    public function collection()
    {
        return $this->activities;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Tanggal',
            'Personel',
            'Kategori',
            'Deskripsi',
            'Reference',
            'Mulai',
            'Waktu Pending/Jeda',
            'Waktu Resume',
            'Selesai',
            'Durasi (menit)',
            'Durasi (jam)',
            'Status'
        ];
    }

    public function map($activity): array
    {
        $reference = '-';
        if ($activity->category == 'PM' && $activity->pmCheck) {
            $reference = 'PM-' . $activity->pmCheck->id;
            if ($activity->pmCheck->pmSchedule && $activity->pmCheck->pmSchedule->asset) {
                $reference .= ' (' . $activity->pmCheck->pmSchedule->asset->name . ')';
            }
        }
        elseif ($activity->category == 'Breakdown' && $activity->ticket) {
            $reference = $activity->ticket->ticket_no;
            if ($activity->ticket->asset) {
                $reference .= ' (' . $activity->ticket->asset->name . ')';
            }
        }

        $duration = $activity->duration ?? 0;
        if ($activity->status === 'running') {
            $duration = $activity->start_time->diffInMinutes(now());
        }

        $endDisplay = 'Running';
        if ($activity->status === 'paused') {
            $endDisplay = $activity->paused_at ? $activity->paused_at->format('H:i') : 'Paused';
        } elseif ($activity->end_time) {
            $endDisplay = $activity->end_time->format('H:i');
        }

        return [
            $activity->id,
            $activity->start_time->format('d/m/Y'),
            $activity->user ? $activity->user->name : '-',
            $activity->category,
            $activity->description ?? '-',
            $reference,
            $activity->start_time->format('H:i'),
            data_get($activity, 'pause_context.pending_at') ? Carbon::parse(data_get($activity, 'pause_context.pending_at'))->format('d/m/Y H:i') : '-',
            data_get($activity, 'pause_context.resumed_at') ? Carbon::parse(data_get($activity, 'pause_context.resumed_at'))->format('d/m/Y H:i') : '-',
            $endDisplay,
            $duration,
            round($duration / 60, 2),
            ucfirst($activity->status)
        ];
    }

    public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
    {
        $rowCount = count($this->activities) + 1;
        $sheet->getStyle('A1:M' . $rowCount)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => '000000']]],
        ]);
        $sheet->getStyle('A1:M1')->getFont()->setBold(true);
        $sheet->getStyle('A1:M1')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A1:M1')->getFill()->applyFromArray([
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'color' => ['argb' => 'FFE0E0E0'],
        ]);
        $sheet->getStyle('E:E')->getAlignment()->setWrapText(true);
        return [];
    }
}

// ============================================================
// SHEET 3: RINGKASAN PER TEKNISI
// ============================================================
class TechnicianActivityExport_Sheet3 implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithStyles, \Maatwebsite\Excel\Concerns\ShouldAutoSize, WithTitle
{
    protected $sheetName;
    protected $technicianSummary;

    public function __construct($sheetName, $technicianSummary)
    {
        $this->sheetName = $sheetName;
        $this->technicianSummary = $technicianSummary;
    }

    public function title(): string
    {
        return $this->sheetName;
    }

    public function collection()
    {
        $data = collect();

        foreach ($this->technicianSummary as $summary) {
            $productivity = $summary['productivity'] ?? 0;
            $status = $productivity >= 100 ? 'Excellent' : ($productivity >= 80 ? 'Good' : ($productivity >= 50 ? 'Fair' : 'Poor'));

            $pmMinutes = $summary['by_category']['PM'] ?? 0;
            $breakdownMinutes = $summary['by_category']['Breakdown'] ?? 0;
            $lainnyaMinutes = $summary['by_category']['Lain-lain'] ?? 0;

            // Merangkai histori clock in/out agar rapi di 1 sel (contoh: 12/03: 08:00 - 17:00)
            $clockStr = '-';
            if (isset($summary['clock_info']) && count($summary['clock_info']) > 0) {
                $clockStr = collect($summary['clock_info'])->map(function ($info) {
                    return Carbon::parse($info['date'])->format('d/m') . ': ' . $info['clock_in'] . ' s/d ' . $info['clock_out'];
                })->implode("\n");
            }

            $data->push([
                $summary['user']->name,
                $summary['total_activities'],
                $summary['net_work_hours'] ?? 0,
                $summary['total_hours'],
                $clockStr,
                $summary['total_minutes'],
                $pmMinutes,
                $breakdownMinutes,
                $lainnyaMinutes,
                $summary['pm_count'],
                $summary['breakdown_count'],
                $summary['other_count'],
                $productivity . '%',
                $status
            ]);
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'Nama Personel',
            'Total Aktivitas',
            'Total Jam Hadir (Net)',
            'Total Jam Aktivitas',
            'Riwayat Clock In/Out',
            'Total Menit Aktivitas',
            'PM (menit)',
            'Breakdown (menit)',
            'Lainnya (menit)',
            'Jumlah PM',
            'Jumlah Breakdown',
            'Jumlah Lainnya',
            'Produktivitas (%)',
            'Status Performance'
        ];
    }

    public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
    {
        $rowCount = count($this->technicianSummary) + 1;

        $sheet->getStyle('A1:N' . $rowCount)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => '000000']]],
        ]);
        $sheet->getStyle('A1:N1')->getFont()->setBold(true);
        $sheet->getStyle('A1:N1')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A1:N1')->getFill()->applyFromArray([
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'color' => ['argb' => 'FFE0E0E0'],
        ]);

        // Aktifkan Wrap Text untuk kolom Riwayat Clock In/Out (Kolom E)
        $sheet->getStyle('E:E')->getAlignment()->setWrapText(true);

        return [];
    }
}

// ============================================================
// SHEET 4: PROGRESS PM
// ============================================================
class TechnicianActivityExport_Sheet4 implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithStyles, \Maatwebsite\Excel\Concerns\ShouldAutoSize, WithTitle
{
    protected $sheetName;
    protected $dateFrom;
    protected $dateTo;

    public function __construct($sheetName, $dateFrom, $dateTo)
    {
        $this->sheetName = $sheetName;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function title(): string
    {
        return $this->sheetName;
    }

    public function collection()
    {
        $data = collect();
        $pmChecks = \App\Models\PmCheck::with(['pmSchedule.asset', 'checkItems', 'technician'])
            ->whereBetween('created_at', [$this->dateFrom, $this->dateTo])
            ->get();
        $pmChecksByTech = $pmChecks->groupBy('technician_id');

        foreach ($pmChecksByTech as $technicianId => $checks) {
            $technician = \App\Models\User::find($technicianId);
            if (!$technician)
                continue;

            $totalItems = 0;
            $completedItems = 0;
            $machines = [];

            foreach ($checks as $check) {
                $checkItems = $check->checkItems;
                $totalItems += $checkItems->count();
                $itemsWithCondition = $checkItems->whereNotNull('condition')->count();
                $completedItems += $itemsWithCondition;

                if ($check->pmSchedule && $check->pmSchedule->asset) {
                    $machines[] = $check->pmSchedule->asset->name;
                }
            }

            $percentage = $totalItems > 0 ? round(($completedItems / $totalItems) * 100, 1) : 0;

            $data->push([
                $technician->name,
                $totalItems,
                $completedItems,
                $totalItems - $completedItems,
                $percentage . '%',
                implode(', ', $machines)
            ]);
        }

        return $data;
    }

    public function headings(): array
    {
        return ['Personel', 'Total Item PM', 'Item Selesai', 'Item Remaining', 'Progress', 'Mesin'];
    }

    public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
    {
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->getStyle('A1:F1')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A1:F1')->getFill()->applyFromArray([
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'color' => ['argb' => 'FFE0E0E0'],
        ]);
        $sheet->getStyle('F:F')->getAlignment()->setWrapText(true);
        return [];
    }
}

// ============================================================
// SHEET 5: BREAKDOWN TICKETS
// ============================================================
class TechnicianActivityExport_Sheet5 implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithMapping, \Maatwebsite\Excel\Concerns\WithStyles, \Maatwebsite\Excel\Concerns\ShouldAutoSize, WithTitle
{
    protected $sheetName;
    protected $dateFrom;
    protected $dateTo;

    public function __construct($sheetName, $dateFrom, $dateTo)
    {
        $this->sheetName = $sheetName;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function title(): string
    {
        return $this->sheetName;
    }

    public function collection()
    {
        $ticketIds = \App\Models\TechnicianActivity::where('category', 'Breakdown')
            ->whereBetween('start_time', [$this->dateFrom, $this->dateTo])
            ->pluck('reference_id')
            ->unique();

        return \App\Models\Ticket::with(['asset', 'requester'])
            ->whereIn('id', $ticketIds)
            ->get();
    }

    public function headings(): array
    {
        return ['Ticket No', 'Asset', 'Subject', 'Requester', 'Status', 'Request Date', 'Assigned To', 'Problem Cause', 'GA Notes'];
    }

    public function map($ticket): array
    {
        return [
            $ticket->ticket_no,
            $ticket->asset ? $ticket->asset->fa_code . ' - ' . $ticket->asset->name : '-',
            $ticket->subject,
            $ticket->requester ? $ticket->requester->name : '-',
            ucfirst($ticket->status),
            $ticket->request_date ? $ticket->request_date->format('d/m/Y') : '-',
            $ticket->assigned_to ?? '-',
            $ticket->problem_cause ?? '-',
            $ticket->ga_notes ?? '-'
        ];
    }

    public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
    {
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        $sheet->getStyle('A1:I1')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A1:I1')->getFill()->applyFromArray([
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'color' => ['argb' => 'FFE0E0E0'],
        ]);
        return [];
    }
}

// ============================================================
// SHEET 6: DETAIL KEHADIRAN (NEW)
// ============================================================
class TechnicianActivityExport_Sheet6 implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithStyles, \Maatwebsite\Excel\Concerns\ShouldAutoSize, WithTitle
{
    protected $sheetName;
    protected $technicianSummary;

    public function __construct($sheetName, $technicianSummary)
    {
        $this->sheetName = $sheetName;
        $this->technicianSummary = $technicianSummary;
    }

    public function title(): string
    {
        return $this->sheetName;
    }

    public function collection()
    {
        $data = collect();

        foreach ($this->technicianSummary as $summary) {
            // Jika dia punya data kehadiran di hari itu
            if (isset($summary['clock_info']) && count($summary['clock_info']) > 0) {
                foreach ($summary['clock_info'] as $info) {
                    $data->push([
                        $summary['user']->name,
                        Carbon::parse($info['date'])->format('d/m/Y'),
                        $info['clock_in'],
                        $info['clock_out']
                    ]);
                }
            }
            else {
                // Jika tidak ada data kehadiran sama sekali di rentang waktu tersebut
                $data->push([
                    $summary['user']->name,
                    '-',
                    'Belum Clock In',
                    '-'
                ]);
            }
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'Nama Personel',
            'Tanggal',
            'Waktu Clock In',
            'Waktu Clock Out'
        ];
    }

    public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
    {
        // Styling untuk header
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        $sheet->getStyle('A1:D1')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A1:D1')->getFill()->applyFromArray([
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'color' => ['argb' => 'FFE0E0E0'],
        ]);

        // Kasih border biar rapi
        $rowCount = $sheet->getHighestRow();
        $sheet->getStyle('A1:D' . $rowCount)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => '000000']]],
        ]);

        return [];
    }
}
