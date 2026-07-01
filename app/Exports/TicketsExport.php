<?php

namespace App\Exports;

use App\Models\Ticket;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class TicketsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $tickets;

    public function __construct($tickets)
    {
        // Load relations needed for notes and response-time calculation
        $this->tickets = $tickets->load([
            'notes.user',
            'requester',
            'asset',
            'technicianActivities',
        ]);
    }

    public function collection()
    {
        return $this->tickets;
    }

    public function headings(): array
    {
        return [
            'Ticket No',
            'Mesin / Aset',
            'Subject',
            'Description',
            'Requester',
            'Department',
            'Status',
            'Request DateTime',
            'Assigned To',
            'Assignment Type',
            'Internal Type',
            'GA PIC',
            'MTC Link',
            'Detail Vendor',
            'Problem Cause',
            'GA Notes',
            'User Notes',
            'Serah Terima Teknisi',
            'Serah Terima User',
            'Tanggal Ditutup',
            'Tanggal Ditolak',
            'Planned Date',
            'Estimated Date',
            'Riwayat Notes/Komentar',
            'Response Time'
        ];
    }

    public function map($ticket): array
    {
        // Asset name with null safety
        $assetName = '-';
        if ($ticket->asset) {
            $assetName = $ticket->asset->name ?? '-';
        }

        // Assignment Type
        $assignmentType = '-';
        if ($ticket->assigned_types && is_array($ticket->assigned_types)) {
            $assignmentType = implode(', ', array_map('ucfirst', $ticket->assigned_types));
        }

        // Internal Type
        $internalType = '-';
        if ($ticket->internal_types && is_array($ticket->internal_types)) {
            $internalType = implode(', ', array_map('strtoupper', $ticket->internal_types));
        }

        // Vendor Details
        $vendorDetails = '-';
        if ($ticket->vendor_details && is_array($ticket->vendor_details) && count($ticket->vendor_details) > 0) {
            $vendorStrings = [];
            foreach ($ticket->vendor_details as $vendor) {
                $vendorInfo = [];
                if (isset($vendor['name'])) {
                    $vendorInfo[] = $vendor['name'];
                }
                if (isset($vendor['contact_person'])) {
                    $vendorInfo[] = "PIC: " . $vendor['contact_person'];
                }
                if (isset($vendor['status'])) {
                    $vendorInfo[] = "Status: " . $vendor['status'];
                }
                $vendorStrings[] = implode(' - ', $vendorInfo);
            }
            $vendorDetails = implode(' | ', $vendorStrings);
        }

        // Requester Name & Department with null safety
        $requesterName = '-';
        $requesterDept = '-';
        if ($ticket->requester) {
            $requesterName = $ticket->requester->name ?? '-';
            $requesterDept = $ticket->requester->department ?? '-';
        }

        // Assigned To
        $assignedTo = $ticket->assigned_to ?? '-';

        // Category & Area - Not implemented, showing placeholder
        $categoryName = '-';
        $areaName = '-';

        // Format planned & estimated dates
        $formattedPlannedDate = '-';
        if ($ticket->planned_date) {
            $formattedPlannedDate = (is_string($ticket->planned_date)
                ? Carbon::parse($ticket->planned_date)->format('d/m/Y')
                : $ticket->planned_date->format('d/m/Y'));
        }

        $formattedEstimatedDate = '-';
        if ($ticket->estimated_date) {
            $formattedEstimatedDate = (is_string($ticket->estimated_date)
                ? Carbon::parse($ticket->estimated_date)->format('d/m/Y')
                : $ticket->estimated_date->format('d/m/Y'));
        }

        // Problem Cause
        $problemCause = $ticket->problem_cause ?? '-';

        // Notes
        $gaNotes = $ticket->ga_notes ?? '-';
        $userNotes = $ticket->user_notes ?? '-';

        // Serah Terima
        $serahTerimaTeknisi = $ticket->serah_terima_teknisi ?? '-';
        $serahTerimaUser = $ticket->serah_terima_user ?? '-';

        // Riwayat Notes/Komentar dari TicketNote
        $notesHistory = '-';
        if ($ticket->notes && $ticket->notes->count() > 0) {
            $notesArray = [];
            foreach ($ticket->notes as $note) {
                $noteUser = $note->user ? $note->user->name : 'Unknown';
                $noteTime = $note->created_at ? (is_string($note->created_at)
                    ? Carbon::parse($note->created_at)->format('d/m/Y H:i')
                    : $note->created_at->format('d/m/Y H:i')) : '';
                $noteContent = $note->note ?? '';
                $notesArray[] = "[{$noteTime}] {$noteUser}: {$noteContent}";
            }
            $notesHistory = implode("\n", $notesArray);
        }

        // Format dates with null safety
        $closedDate = $ticket->closed_date;
        $rejectedDate = $ticket->rejected_date;

        $formattedRequestDate = '-';
        if ($ticket->created_at) {
            $formattedRequestDate = (is_string($ticket->created_at)
                ? Carbon::parse($ticket->created_at)->format('d/m/Y H:i')
                : $ticket->created_at->format('d/m/Y H:i'));
        }

        $formattedClosedDate = '-';
        if ($closedDate) {
            $formattedClosedDate = (is_string($closedDate)
                ? Carbon::parse($closedDate)->format('d/m/Y')
                : $closedDate->format('d/m/Y'));
        }

        $formattedRejectedDate = '-';
        if ($rejectedDate) {
            $formattedRejectedDate = (is_string($rejectedDate)
                ? Carbon::parse($rejectedDate)->format('d/m/Y')
                : $rejectedDate->format('d/m/Y'));
        }

        $firstTechnicianStart = $ticket->technicianActivities
            ->filter(fn($activity) => !empty($activity->start_time))
            ->sortBy('start_time')
            ->first();

        $responseTime = '-';
        if ($ticket->created_at && $firstTechnicianStart && $firstTechnicianStart->start_time) {
            $responseTime = $this->formatDuration(
                $ticket->created_at->diffInMinutes($firstTechnicianStart->start_time)
            );
        }

        return [
            $ticket->ticket_no,
            $assetName,
            $ticket->subject ?? '-',
            $ticket->description ?? '-',
            $requesterName,
            $requesterDept,
            ucfirst($ticket->status),
            $formattedRequestDate,
            $assignedTo,
            $assignmentType,
            $internalType,
            $ticket->ga_pic_name ?? '-',
            $ticket->mtc_ticket_link ?? '-',
            $vendorDetails,
            $problemCause,
            $gaNotes,
            $userNotes,
            $serahTerimaTeknisi,
            $serahTerimaUser,
            $formattedClosedDate,
            $formattedRejectedDate,
            $formattedPlannedDate,
            $formattedEstimatedDate,
            $notesHistory,
            $responseTime,
        ];
    }

    private function formatDuration(int $minutes): string
    {
        $days = intdiv($minutes, 1440);
        $hours = intdiv($minutes % 1440, 60);
        $remainingMinutes = $minutes % 60;

        $parts = [];

        if ($days > 0) {
            $parts[] = $days . ' hari';
        }

        if ($hours > 0) {
            $parts[] = $hours . ' jam';
        }

        if ($remainingMinutes > 0 || empty($parts)) {
            $parts[] = $remainingMinutes . ' menit';
        }

        return implode(' ', $parts);
    }

    public function styles(Worksheet $sheet)
    {
        // Hitung total baris untuk border
        $rowCount = count($this->tickets) + 1; // +1 karena heading

        // Border untuk semua sel
        $sheet->getStyle('A1:Y' . $rowCount)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ]);

        // Auto wrap text untuk kolom yang membutuhkan
        $sheet->getStyle('B:B')->getAlignment()->setWrapText(true); // Mesin / Aset
        $sheet->getStyle('C:C')->getAlignment()->setWrapText(true); // Subject
        $sheet->getStyle('D:D')->getAlignment()->setWrapText(true); // Description
        $sheet->getStyle('O:O')->getAlignment()->setWrapText(true); // Vendor Details
        $sheet->getStyle('P:P')->getAlignment()->setWrapText(true); // Problem Cause
        $sheet->getStyle('Q:Q')->getAlignment()->setWrapText(true); // GA Notes
        $sheet->getStyle('R:R')->getAlignment()->setWrapText(true); // User Notes
        $sheet->getStyle('S:S')->getAlignment()->setWrapText(true); // Serah Terima Teknisi
        $sheet->getStyle('T:T')->getAlignment()->setWrapText(true); // Serah Terima User
        $sheet->getStyle('X:X')->getAlignment()->setWrapText(true); // Riwayat Notes
        $sheet->getStyle('Y:Y')->getAlignment()->setWrapText(true); // Response Time

        // Bold header
        $sheet->getStyle('A1:Y1')->getFont()->setBold(true);

        // Center header text
        $sheet->getStyle('A1:Y1')->getAlignment()->setHorizontal('center');

        // Warna latar belakang header
        $sheet->getStyle('A1:Y1')->getFill()->applyFromArray([
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'color' => ['argb' => 'FFE0E0E0'],
        ]);

        // Set tinggi row header
        $sheet->getRowDimension(1)->setRowHeight(25);

        return [];
    }
}
