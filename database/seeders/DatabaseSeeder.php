<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            OpdSeeder::class,
            UserSeeder::class,
            QrcodeSeeder::class,
            AttendanceSeeder::class,
        ]);
    }
}
