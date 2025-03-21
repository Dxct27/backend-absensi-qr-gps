<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateSuperAdmin extends Command
{
    protected $signature = 'superadmin:create {email=superadmin@superadmin.com} {--password=}';
    protected $description = 'Create a superadmin user';

    public function handle()
    {
        $email = $this->argument('email') ?? 'superadmin@superadmin.com';
        $password = $this->option('password') ?? 'superadmin123';

        if (User::where('email', $email)->exists()) {
            $this->error("User with email $email already exists!");
            return;
        }

        User::create([
            'name' => 'Super Admin',
            'email' => $email,
            'password' => Hash::make($password),
            'group' => 'superadmin',
        ]);

        $this->info("Superadmin created successfully! Email: $email, Password: $password");
    }
}
