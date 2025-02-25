<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Qrcode;
use App\Models\Opd;

class QrcodeSeeder extends Seeder
{
    public function run()
    {
        $opd = Opd::first();

        Qrcode::create([
            'opd_id' => $opd->id,
            'name' => 'Main Office Entrance',
            'value' => 'QR123456',
            'latitude' => '-6.200000',
            'longitude' => '106.816666',
            'radius' => 10,
        ]);
    }
}
