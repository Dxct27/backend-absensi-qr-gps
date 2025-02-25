<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Opd;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        $opd = Opd::first();

        User::create([
            'nip' => '123456789',
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'group' => 'admin',
            'opd_id' => $opd->id,
        ]);
    }
}
