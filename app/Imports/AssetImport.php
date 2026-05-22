<?php

namespace App\Imports;

use App\Models\Asset;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AssetImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Asset([
            // fa_code diisi otomatis/acak jika sudah tidak digunakan di view
            'fa_code'   => 'FA-' . uniqid(), 
            'name'      => $row['equipment'],  // Header di Excel harus: equipment
            'type'      => $row['type'],       // Header di Excel harus: type
            'equip_tag' => $row['equip_tag'],  // Header di Excel harus: equip_tag
            'location'  => $row['location'],   // Header di Excel harus: location
        ]);
    }
}