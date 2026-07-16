<?php

namespace App\Exports;

use App\Models\InternalTicket;
use App\Models\PmCheck;
use App\Models\TechnicianActivity;
use App\Models\TechnicianAttendance;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ManagerReportExport implements WithMultipleSheets
{
    protected string $startDate;
    protected string $endDate;
    protected ?int $technicianId;
    protected Collection $technicians;
    protected Collection $activities;
    protected Collection $pmChecks;
    protected Collection $tickets;
    protected Collection $internalTickets;
    protected Collection $attendances;

    public function __construct(string $startDate, string $endDate, ?int $technicianId = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->technicianId = $technicianId;

        $this->loadData();
    }

    public function sheets(): array
    {
        return [
            new ManagerReportSheet('Ringkasan Eksekutif', [], $this->executiveSummaryRows(), false),
            new ManagerReportSheet('Detail PM Mesin', $this->pmMachineHeadings(), $this->pmMachineRows()),
            new ManagerReportSheet('Summary PM Teknisi', $this->pmTechnicianHeadings(), $this->pmTechnicianRows()),
            new ManagerReportSheet('Detail Tiket Breakdown', $this->ticketDetailHeadings(), $this->ticketDetailRows()),
            new ManagerReportSheet('Summary Tiket Teknisi', $this->ticketTechnicianHeadings(), $this->ticketTechnicianRows()),
            new ManagerReportSheet('Distribusi Tiket', $this->ticketDistributionHeadings(), $this->ticketDistributionRows()),
            new ManagerReportSheet('Internal dan Lainnya', $this->internalHeadings(), $this->internalRows()),
            new ManagerReportSheet('Scorecard Teknisi', $this->scorecardHeadings(), $this->scorecardRows()),
        ];
    }

    protected function loadData(): void
    {
        $technicianQuery = User::whereIn('role', ['mtc', 'MTC', 'teknisi', 'technician']);
        if ($this->technicianId) {
            $technicianQuery = User::where('id', $this->technicianId);
        }
        $this->technicians = $technicianQuery->orderBy('name')->get();

        $technicianIds = $this->technicians->pluck('id');

        $this->activities = TechnicianActivity::with([
            'user',
            'ticket.asset',
            'ticket.requester',
            'pmCheck.pmSchedule.asset',
            'pmCheck.checkItems',
            'internalTicket.asset',
            'internalTicket.requester',
            'internalTicket.pmCheckItem.checklistTemplate',
        ])
            ->whereDate('start_time', '>=', $this->startDate)
            ->whereDate('start_time', '<=', $this->endDate)
            ->when($this->technicianId, fn($query) => $query->where('user_id', $this->technicianId))
            ->orderBy('start_time')
            ->get();

        $this->pmChecks = PmCheck::with(['pmSchedule.asset', 'technician', 'checkItems'])
            ->where(function ($query) {
                $query->whereBetween('check_date', [$this->startDate, $this->endDate])
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('check_date')
                            ->whereBetween('due_date', [$this->startDate, $this->endDate]);
                    });
            })
            ->when($this->technicianId, fn($query) => $query->where('technician_id', $this->technicianId))
            ->get();

        $ticketIdsFromActivities = $this->activities
            ->where('category', 'Breakdown')
            ->pluck('reference_id')
            ->filter()
            ->unique();

        $this->tickets = Ticket::with(['asset', 'requester'])
            ->where(function ($query) use ($ticketIdsFromActivities) {
                $query->whereBetween('request_date', [$this->startDate, $this->endDate]);
                if ($ticketIdsFromActivities->isNotEmpty()) {
                    $query->orWhereIn('id', $ticketIdsFromActivities);
                }
            })
            ->orderBy('request_date')
            ->get();

        $internalIdsFromActivities = $this->activities
            ->where('category', 'Lain-lain')
            ->pluck('reference_id')
            ->filter()
            ->unique();

        $this->internalTickets = InternalTicket::with(['asset', 'requester', 'pmCheckItem.checklistTemplate'])
            ->where(function ($query) use ($internalIdsFromActivities) {
                $query->whereBetween('request_date', [$this->startDate, $this->endDate]);
                if ($internalIdsFromActivities->isNotEmpty()) {
                    $query->orWhereIn('id', $internalIdsFromActivities);
                }
            })
            ->orderBy('request_date')
            ->get();

        $this->attendances = TechnicianAttendance::with('user')
            ->whereDate('date', '>=', $this->startDate)
            ->whereDate('date', '<=', $this->endDate)
            ->when($technicianIds->isNotEmpty(), fn($query) => $query->whereIn('user_id', $technicianIds))
            ->orderBy('date')
            ->get();
    }

    protected function executiveSummaryRows(): array
    {
        $ticketActivityGroups = $this->activities->where('category', 'Breakdown')->groupBy('reference_id');
        $avgResponse = $this->average($this->tickets->map(function ($ticket) use ($ticketActivityGroups) {
            $first = ($ticketActivityGroups->get($ticket->id) ?? collect())->sortBy('start_time')->first();
            return $first ? $ticket->created_at->diffInMinutes($first->start_time) : null;
        }));

        $totalAttendanceHours = $this->attendanceHours($this->attendances);
        $totalActivityHours = round($this->activities->sum(fn($activity) => $this->activityMinutes($activity)) / 60, 2);
        $productivity = $totalAttendanceHours > 0 ? round(($totalActivityHours / $totalAttendanceHours) * 100, 2) : 0;

        $rows = [
            ['LAPORAN EFEKTIVITAS TEKNISI'],
            ['Periode', $this->formatDate($this->startDate) . ' - ' . $this->formatDate($this->endDate)],
            ['Teknisi', $this->technicianId ? optional($this->technicians->first())->name : 'Semua Teknisi'],
            ['Generated', now()->format('d/m/Y H:i')],
            [],
            ['METRIK UTAMA', 'Nilai'],
            ['Total PM Terjadwal', $this->pmChecks->count()],
            ['Total PM Selesai/Closed', $this->pmChecks->whereIn('status', ['completed', 'verified', 'approved', 'closed'])->count()],
            ['Total Tiket Masuk', $this->tickets->count()],
            ['Total Tiket Closed', $this->tickets->where('status', 'closed')->count()],
            ['Total Tiket Internal Masuk', $this->internalTickets->count()],
            ['Total Tiket Internal Closed', $this->internalTickets->where('status', 'closed')->count()],
            ['Rata-rata Response Time Tiket (menit)', $avgResponse],
            ['Rata-rata Durasi Pengerjaan Tiket (menit)', $this->average($ticketActivityGroups->map(fn($items) => $items->sum(fn($activity) => $this->activityMinutes($activity))))],
            ['Total Jam Kerja Tim (Net)', $totalAttendanceHours],
            ['Total Jam Aktivitas', $totalActivityHours],
            ['Overall Productivity (%)', $productivity],
            [],
            ['TOP 5 TEKNISI PRODUKTIF', 'Jam Aktivitas', 'Produktivitas (%)'],
        ];

        foreach ($this->scorecardRows()->sortByDesc(fn($row) => $row[4])->take(5) as $row) {
            $rows[] = [$row[0], $row[4], $row[5]];
        }

        return $rows;
    }

    protected function pmMachineHeadings(): array
    {
        return [
            'Nama Mesin', 'Tipe Jadwal', 'PIC/Teknisi', 'Week Number', 'Total Item Checklist',
            'Item Sudah Dicek', 'Item Belum Dicek', 'Item Bermasalah', 'Item Butuh Follow Up',
            'Progress (%)', 'Tanggal Mulai Pengerjaan', 'Tanggal Selesai', 'Durasi Pengerjaan (menit)',
            'Durasi Pause Total (menit)', 'Jumlah Pause', 'Shift', 'Status PM',
        ];
    }

    protected function pmMachineRows(): array
    {
        $pmActivities = $this->activities->where('category', 'PM')->groupBy('reference_id');

        return $this->pmChecks->map(function ($check) use ($pmActivities) {
            $items = $check->checkItems;
            $checked = $items->filter(fn($item) => filled($item->condition))->count();
            $activities = $pmActivities->get($check->id) ?? collect();
            $firstActivity = $activities->sortBy('start_time')->first();
            $lastActivity = $activities->sortByDesc(fn($activity) => $activity->end_time ?: $activity->start_time)->first();

            return [
                optional(optional($check->pmSchedule)->asset)->name ?? '-',
                optional($check->pmSchedule)->schedule_type ?? '-',
                optional($check->technician)->name ?? $check->technician_name ?? '-',
                $check->week_number ?? '-',
                $items->count(),
                $checked,
                $items->count() - $checked,
                $items->filter(fn($item) => $this->isNotOk($item->condition))->count(),
                $items->filter(fn($item) => filled($item->next_action))->count(),
                $items->count() > 0 ? round(($checked / $items->count()) * 100, 2) : 0,
                $this->formatDateTime(optional($firstActivity)->start_time),
                $this->formatDateTime(optional($lastActivity)->end_time),
                $activities->sum(fn($activity) => $this->activityMinutes($activity)),
                $activities->sum('total_pause_minutes'),
                $activities->sum('pause_count'),
                $check->shift ?? '-',
                $check->status ?? '-',
            ];
        })->values()->all();
    }

    protected function pmTechnicianHeadings(): array
    {
        return [
            'Nama Teknisi', 'Jumlah Mesin Ditangani', 'Total Item Ditugaskan', 'Total Item Selesai',
            'Total Item Belum', 'Progress (%)', 'Total Durasi Pengerjaan PM (jam)',
            'Rata-rata Item per Shift', 'Rata-rata Durasi per Mesin (menit)', 'Item Bermasalah', 'Item Follow Up',
        ];
    }

    protected function pmTechnicianRows(): Collection
    {
        $pmActivities = $this->activities->where('category', 'PM')->groupBy('user_id');
        $attendanceByUser = $this->attendances->groupBy('user_id');

        return $this->technicians->map(function ($tech) use ($pmActivities, $attendanceByUser) {
            $checks = $this->pmChecks->where('technician_id', $tech->id);
            $items = $checks->flatMap->checkItems;
            $done = $items->filter(fn($item) => filled($item->condition))->count();
            $total = $items->count();
            $duration = ($pmActivities->get($tech->id) ?? collect())->sum(fn($activity) => $this->activityMinutes($activity));
            $shifts = max(1, ($attendanceByUser->get($tech->id) ?? collect())->count());
            $machineCount = $checks->pluck('pm_schedule_id')->filter()->unique()->count();

            return [
                $tech->name,
                $machineCount,
                $total,
                $done,
                $total - $done,
                $total > 0 ? round(($done / $total) * 100, 2) : 0,
                round($duration / 60, 2),
                round($done / $shifts, 2),
                $machineCount > 0 ? round($duration / $machineCount, 2) : 0,
                $items->filter(fn($item) => $this->isNotOk($item->condition))->count(),
                $items->filter(fn($item) => filled($item->next_action))->count(),
            ];
        })->values();
    }

    protected function ticketDetailHeadings(): array
    {
        return [
            'No Tiket', 'Tanggal Masuk', 'Jam Masuk', 'Mesin/Aset', 'Subject', 'Requester', 'Department',
            'Status', 'Assigned To', 'GA PIC', 'MTC PIC', 'Teknisi Yang Mengerjakan', 'Jumlah Teknisi',
            'Response Time (menit)', 'Tanggal Mulai Dikerjakan', 'Tanggal Selesai', 'Durasi Total Pengerjaan (menit)',
            'Durasi Total Pause (menit)', 'Jumlah Penjedaan', 'Jumlah Sesi Pengerjaan', 'Lead Time (jam)',
            'Problem Cause', 'Planned Date', 'PR Number',
        ];
    }

    protected function ticketDetailRows(): array
    {
        $activityGroups = $this->activities->where('category', 'Breakdown')->groupBy('reference_id');

        return $this->tickets->map(function ($ticket) use ($activityGroups) {
            $activities = $activityGroups->get($ticket->id) ?? collect();
            $first = $activities->sortBy('start_time')->first();

            return [
                $ticket->ticket_no,
                $this->formatDate($ticket->request_date),
                $this->formatDateTime($ticket->created_at),
                optional($ticket->asset)->name ?? '-',
                $ticket->subject ?? '-',
                optional($ticket->requester)->name ?? '-',
                optional($ticket->requester)->department ?? '-',
                $ticket->status ?? '-',
                $ticket->assigned_to ?? '-',
                $ticket->ga_pic_name ?? '-',
                $ticket->mtc_pic_name ?? '-',
                $activities->pluck('user.name')->filter()->unique()->implode(', ') ?: '-',
                $activities->pluck('user_id')->filter()->unique()->count(),
                $first ? $ticket->created_at->diffInMinutes($first->start_time) : '-',
                $this->formatDateTime(optional($first)->start_time),
                $this->formatDateTime($ticket->closed_date),
                $activities->sum(fn($activity) => $this->activityMinutes($activity)),
                $activities->sum('total_pause_minutes'),
                $activities->sum('pause_count'),
                $activities->count(),
                $ticket->closed_date ? round($ticket->created_at->diffInHours($ticket->closed_date), 2) : '-',
                $ticket->problem_cause ?? '-',
                $this->formatDate($ticket->planned_date),
                $ticket->pr_number ?? '-',
            ];
        })->values()->all();
    }

    protected function ticketTechnicianHeadings(): array
    {
        return [
            'Nama Teknisi', 'Total Tiket Dikerjakan', 'Total Sesi Pengerjaan', 'Total Durasi Pengerjaan (jam)',
            'Rata-rata Durasi per Tiket (menit)', 'Total Penjedaan', 'Total Durasi Pause (menit)',
            'Tiket Selesai (Closed)', 'Rata-rata Response Time (menit)', 'Rata-rata Lead Time (jam)',
        ];
    }

    protected function ticketTechnicianRows(): array
    {
        $ticketsById = $this->tickets->keyBy('id');

        return $this->technicians->map(function ($tech) use ($ticketsById) {
            $activities = $this->activities->where('category', 'Breakdown')->where('user_id', $tech->id);
            $ticketIds = $activities->pluck('reference_id')->filter()->unique();
            $tickets = $ticketIds->map(fn($id) => $ticketsById->get($id))->filter();
            $duration = $activities->sum(fn($activity) => $this->activityMinutes($activity));

            return [
                $tech->name,
                $ticketIds->count(),
                $activities->count(),
                round($duration / 60, 2),
                $ticketIds->count() > 0 ? round($duration / $ticketIds->count(), 2) : 0,
                $activities->sum('pause_count'),
                $activities->sum('total_pause_minutes'),
                $tickets->where('status', 'closed')->count(),
                $this->average($ticketIds->map(function ($ticketId) use ($activities, $ticketsById) {
                    $ticket = $ticketsById->get($ticketId);
                    $first = $activities->where('reference_id', $ticketId)->sortBy('start_time')->first();
                    return ($ticket && $first) ? $ticket->created_at->diffInMinutes($first->start_time) : null;
                })),
                $this->average($tickets->map(fn($ticket) => $ticket->closed_date ? $ticket->created_at->diffInHours($ticket->closed_date) : null)),
            ];
        })->values()->all();
    }

    protected function ticketDistributionHeadings(): array
    {
        return [
            'Bulan', 'Tiket Masuk', 'Tiket Closed', 'Tiket Open', 'Tiket On Progress', 'Tiket Pending/Schedule',
            'Tiket Rejected', 'Rate Penyelesaian (%)', 'Rata-rata Response Time (menit)', 'Rata-rata Lead Time (jam)',
        ];
    }

    protected function ticketDistributionRows(): array
    {
        $activityGroups = $this->activities->where('category', 'Breakdown')->groupBy('reference_id');

        return $this->tickets
            ->groupBy(fn($ticket) => Carbon::parse($ticket->request_date ?: $ticket->created_at)->format('Y-m'))
            ->sortKeys()
            ->map(function ($tickets, $month) use ($activityGroups) {
                $closed = $tickets->where('status', 'closed')->count();
                return [
                    $month,
                    $tickets->count(),
                    $closed,
                    $tickets->where('status', 'open')->count(),
                    $tickets->where('status', 'onprogress')->count(),
                    $tickets->where('status', 'schedule')->count(),
                    $tickets->where('status', 'rejected')->count(),
                    $tickets->count() > 0 ? round(($closed / $tickets->count()) * 100, 2) : 0,
                    $this->average($tickets->map(function ($ticket) use ($activityGroups) {
                        $first = ($activityGroups->get($ticket->id) ?? collect())->sortBy('start_time')->first();
                        return $first ? $ticket->created_at->diffInMinutes($first->start_time) : null;
                    })),
                    $this->average($tickets->map(fn($ticket) => $ticket->closed_date ? $ticket->created_at->diffInHours($ticket->closed_date) : null)),
                ];
            })->values()->all();
    }

    protected function internalHeadings(): array
    {
        return [
            'No Tiket Internal', 'Sumber', 'Asal PM Item', 'Tanggal Masuk', 'Mesin/Aset', 'Subject', 'Deskripsi',
            'Ditugaskan Ke', 'Prioritas', 'Status', 'Target Date', 'Tanggal Mulai', 'Tanggal Selesai',
            'Durasi Pengerjaan (menit)', 'Hasil Pekerjaan', 'Requester',
        ];
    }

    protected function internalRows(): array
    {
        $activityGroups = $this->activities->where('category', 'Lain-lain')->groupBy('reference_id');

        return $this->internalTickets->map(function ($ticket) use ($activityGroups) {
            $activities = $activityGroups->get($ticket->id) ?? collect();
            $pmItem = $ticket->pmCheckItem;

            return [
                $ticket->ticket_no,
                $ticket->source_type ?? '-',
                $pmItem ? (($pmItem->checklistTemplate->item_name ?? 'PM Item') . ' #' . $pmItem->id) : '-',
                $this->formatDate($ticket->request_date),
                optional($ticket->asset)->name ?? '-',
                $ticket->subject ?? '-',
                $ticket->description ?? '-',
                $ticket->assigned_to_name ?? '-',
                $ticket->priority ?? '-',
                $ticket->status ?? '-',
                $this->formatDate($ticket->target_date),
                $this->formatDateTime($ticket->started_at),
                $this->formatDateTime($ticket->closed_at),
                $activities->sum(fn($activity) => $this->activityMinutes($activity)),
                $ticket->work_result ?? '-',
                optional($ticket->requester)->name ?? '-',
            ];
        })->values()->all();
    }

    protected function scorecardHeadings(): array
    {
        return [
            'Nama Teknisi', 'Role', 'Total Hari Hadir', 'Total Jam Hadir (Net)', 'Total Jam Aktivitas',
            'Produktivitas (%)', 'Mesin PM Ditangani', 'Item PM Selesai', 'Jam Kerja PM', 'Tiket Ditangani',
            'Jam Kerja Breakdown', 'Rata-rata Response Time (menit)', 'Aktivitas Lain-lain', 'Jam Kerja Lainnya',
            'Distribusi PM (%)', 'Distribusi Breakdown (%)', 'Distribusi Lainnya (%)', 'Rating Performance',
        ];
    }

    protected function scorecardRows(): Collection
    {
        $ticketsById = $this->tickets->keyBy('id');

        return $this->technicians->map(function ($tech) use ($ticketsById) {
            $activities = $this->activities->where('user_id', $tech->id);
            $attendance = $this->attendances->where('user_id', $tech->id);
            $activityMinutes = $activities->sum(fn($activity) => $this->activityMinutes($activity));
            $activityHours = round($activityMinutes / 60, 2);
            $attendanceHours = $this->attendanceHours($attendance);
            $productivity = $attendanceHours > 0 ? round(($activityHours / $attendanceHours) * 100, 2) : 0;
            $pmMinutes = $activities->where('category', 'PM')->sum(fn($activity) => $this->activityMinutes($activity));
            $breakdownMinutes = $activities->where('category', 'Breakdown')->sum(fn($activity) => $this->activityMinutes($activity));
            $otherMinutes = $activities->where('category', 'Lain-lain')->sum(fn($activity) => $this->activityMinutes($activity));
            $pmChecks = $this->pmChecks->where('technician_id', $tech->id);
            $breakdownTicketIds = $activities->where('category', 'Breakdown')->pluck('reference_id')->filter()->unique();

            return [
                $tech->name,
                $tech->role,
                $attendance->count(),
                $attendanceHours,
                $activityHours,
                $productivity,
                $pmChecks->pluck('pm_schedule_id')->filter()->unique()->count(),
                $pmChecks->flatMap->checkItems->filter(fn($item) => filled($item->condition))->count(),
                round($pmMinutes / 60, 2),
                $breakdownTicketIds->count(),
                round($breakdownMinutes / 60, 2),
                $this->average($breakdownTicketIds->map(function ($ticketId) use ($activities, $ticketsById) {
                    $ticket = $ticketsById->get($ticketId);
                    $first = $activities->where('category', 'Breakdown')->where('reference_id', $ticketId)->sortBy('start_time')->first();
                    return ($ticket && $first) ? $ticket->created_at->diffInMinutes($first->start_time) : null;
                })),
                $activities->where('category', 'Lain-lain')->count(),
                round($otherMinutes / 60, 2),
                $activityMinutes > 0 ? round(($pmMinutes / $activityMinutes) * 100, 2) : 0,
                $activityMinutes > 0 ? round(($breakdownMinutes / $activityMinutes) * 100, 2) : 0,
                $activityMinutes > 0 ? round(($otherMinutes / $activityMinutes) * 100, 2) : 0,
                $this->rating($productivity),
            ];
        })->values();
    }

    protected function activityMinutes(TechnicianActivity $activity): int
    {
        if ($activity->status === 'running' && $activity->start_time) {
            return $activity->start_time->diffInMinutes(now());
        }

        return (int) ($activity->duration ?? 0);
    }

    protected function attendanceHours(Collection $attendances): float
    {
        $minutes = $attendances->sum(function ($attendance) {
            if (!$attendance->clock_in) {
                return 0;
            }

            $end = $attendance->clock_out ?: now();
            return $attendance->clock_in->diffInMinutes($end);
        });

        return round($minutes / 60, 2);
    }

    protected function average(Collection $values): float
    {
        $filtered = $values->filter(fn($value) => is_numeric($value));
        return $filtered->count() > 0 ? round($filtered->avg(), 2) : 0;
    }

    protected function isNotOk($condition): bool
    {
        if (!filled($condition)) {
            return false;
        }

        return !in_array(strtolower(trim((string) $condition)), ['ok', 'baik', 'normal'], true);
    }

    protected function rating(float $productivity): string
    {
        if ($productivity >= 90) {
            return 'Excellent';
        }
        if ($productivity >= 70) {
            return 'Good';
        }
        if ($productivity >= 50) {
            return 'Fair';
        }

        return 'Poor';
    }

    protected function formatDate($value): string
    {
        return $value ? Carbon::parse($value)->format('d/m/Y') : '-';
    }

    protected function formatDateTime($value): string
    {
        return $value ? Carbon::parse($value)->format('d/m/Y H:i') : '-';
    }
}

class ManagerReportSheet implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    protected string $title;
    protected array $headings;
    protected array $rows;
    protected bool $withTableHeader;

    public function __construct(string $title, array $headings, $rows, bool $withTableHeader = true)
    {
        $this->title = $title;
        $this->headings = $headings;
        $this->rows = $rows instanceof Collection ? $rows->values()->all() : $rows;
        $this->withTableHeader = $withTableHeader;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function headings(): array
    {
        return $this->withTableHeader ? $this->headings : [];
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function styles(Worksheet $sheet)
    {
        $highestColumn = $sheet->getHighestColumn();
        $highestRow = $sheet->getHighestRow();

        if ($this->withTableHeader) {
            $sheet->getStyle('A1:' . $highestColumn . '1')->getFont()->setBold(true);
            $sheet->getStyle('A1:' . $highestColumn . '1')->getAlignment()->setHorizontal('center');
            $sheet->getStyle('A1:' . $highestColumn . '1')->getFill()->applyFromArray([
                'fillType' => Fill::FILL_SOLID,
                'color' => ['argb' => 'FFE0E0E0'],
            ]);
        } else {
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A6:B6')->getFont()->setBold(true);
            $sheet->getStyle('A19:C19')->getFont()->setBold(true);
        }

        if ($highestRow > 0) {
            $sheet->getStyle('A1:' . $highestColumn . $highestRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FFDDDDDD'],
                    ],
                ],
            ]);
        }

        $sheet->getStyle('A1:' . $highestColumn . $highestRow)->getAlignment()->setWrapText(true);

        return [];
    }
}
