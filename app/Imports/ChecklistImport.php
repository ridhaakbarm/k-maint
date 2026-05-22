<?php

namespace App\Imports;

use App\Models\ChecklistTemplate;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ChecklistImport implements ToModel, WithHeadingRow
{
    protected $pm_schedule_id;

    public function __construct($pm_schedule_id)
    {
        $this->pm_schedule_id = $pm_schedule_id;
    }

    public function model(array $row)
    {
        // Logika konversi kolom 'weeks' (misal: "1-52") menjadi array
        $weeks = [];
        if (isset($row['weeks'])) {
            $weekString = (string) $row['weeks'];
            if (str_contains($weekString, '-')) {
                $parts = explode('-', $weekString);
                $weeks = range((int)$parts[0], (int)$parts[1]);
            } else {
                $weeks = array_map('intval', explode(',', $weekString));
            }
        }

        return new ChecklistTemplate([
            'pm_schedule_id' => $this->pm_schedule_id,
            'item_name'      => $row['item_name'] ?? 'Tanpa Nama',
            'checked_part'   => $row['checked_part'] ?? '-',
            'instructions'   => $row['instructions'] ?? '-',
            'check_standard' => $row['check_standard'] ?? '-',
            'order'          => isset($row['order']) ? (int)$row['order'] : 0,
            'is_active'      => true,
            'active_weeks'   => $weeks,
        ]);
    }
}