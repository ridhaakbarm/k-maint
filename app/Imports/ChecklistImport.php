<?php

namespace App\Imports;

use App\Models\ChecklistTemplate;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ChecklistImport implements ToCollection, WithHeadingRow
{
    protected $pm_schedule_id;

    public function __construct($pm_schedule_id)
    {
        $this->pm_schedule_id = $pm_schedule_id;
    }

    public function collection(Collection $rows)
    {
        $insertedCount = 0;

        foreach ($rows as $row) {
            // Ambil data dengan prioritas header bahasa Indonesia (dari hasil export) lalu bahasa Inggris
            $itemName = $row['item_checklist'] ?? $row['item_name'] ?? null;

            // Jika tidak ada item_name atau item_checklist sama sekali, anggap baris ini kosong atau tidak valid
            if (empty($itemName)) {
                continue;
            }

            $checkedPart = $row['bagian_yang_dicek'] ?? $row['checked_part'] ?? '-';
            $inspectionCondition = $row['kondisi_pemeriksaan'] ?? $row['inspection_condition'] ?? 'Running / Off';
            $instructions = $row['instruksi'] ?? $row['instructions'] ?? '-';
            $checkStandard = $row['standar_pengecekan'] ?? $row['check_standard'] ?? '-';
            $order = $row['urutan'] ?? $row['order'] ?? 0;

            // Logika konversi kolom 'weeks' atau 'minggu_aktif' menjadi array
            $weeks = [];
            $weekString = $row['minggu_aktif'] ?? $row['weeks'] ?? null;

            if ($weekString !== null && $weekString !== '' && $weekString !== '-') {
                $weekString = (string) $weekString;
                if (str_contains($weekString, '-')) {
                    $parts = explode('-', $weekString);
                    if (count($parts) == 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
                        $weeks = range((int) $parts[0], (int) $parts[1]);
                    }
                } else {
                    $weeks = array_map('intval', array_filter(explode(',', str_replace(' ', '', $weekString))));
                }
            }

            ChecklistTemplate::create([
                'pm_schedule_id' => $this->pm_schedule_id,
                'item_name' => $itemName,
                'checked_part' => $checkedPart,
                'inspection_condition' => $inspectionCondition,
                'instructions' => $instructions,
                'check_standard' => $checkStandard,
                'order' => (int) $order,
                'is_active' => true,
                'active_weeks' => $weeks,
            ]);

            $insertedCount++;
        }

        if ($insertedCount === 0) {
            throw new \Exception("Tidak ada data yang berhasil diimport. Pastikan format kolom Excel sesuai dengan template (misal: 'Item Checklist', 'Instruksi', dll).");
        }
    }
}
