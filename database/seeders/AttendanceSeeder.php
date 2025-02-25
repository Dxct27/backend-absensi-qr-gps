<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Opd;
use App\Models\Qrcode;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run()
    {
        $user = User::first();
        $opd = Opd::first();
        $qrcode = Qrcode::first();

        if (!$user || !$opd || !$qrcode) {
            return;
        }

        Attendance::create([
            'user_id' => $user->id,
            'opd_id' => $opd->id,
            'qrcode_id' => $qrcode->id,
            'date' => Carbon::now()->format('Y-m-d'),
            'timestamp' => Carbon::now()->format('H:i:s'),
            'latitude' => '-6.200000',
            'longitude' => '106.816666',
            'status' => 'Present',
            'notes' => 'Checked in on time',
            'attachments' => null,
        ]);
    }
}
