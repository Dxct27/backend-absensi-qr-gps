<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Opd;

class OpdSeeder extends Seeder
{
    public function run()
    {
        Opd::factory()->count(5)->create();
    }
}
