<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\Opd;

class ImportUser extends Command
{
    protected $signature = 'users:importAll';
    protected $description = 'Import users dynamically for each OPD or a specific OPD from the external API';

    public function handle()
    {
        $apiBaseUrl = 'https://api-splp.layanan.go.id/simpeg/1.0/pns_unker?';
        $apiKey = config('app.api_keys.splp');
        $limit = 10000;
        $offset = 0;

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'ApiKey' => $apiKey,
        ])->get($apiBaseUrl, [
                    'offset' => $offset,
                    'limit' => $limit,
                ]);

        if ($response->failed()) {
            $this->error("Failed to fetch users");
            $this->error("API Response: " . $response->body());

            $errorData = $response->json();
            if (isset($errorData['error_code']) && $errorData['error_code'] == 1304) {
                $this->error("Authentication failed! Please check your API key.");
                return;
            }
        }

        $data = $response->json();

        if (!isset($data['result']) || !is_array($data['result'])) {
            $this->error("Invalid API response format");
        }

        $users = $data['result'];

        foreach ($users as $userData) {
            $nip = $userData['nip'] ?? null;
            $name = trim($userData['nama_lengkap'] ?? '');
            $email = isset($userData['email']) ? trim($userData['email']) : null;
            $opdId = $userData['opd_id'] ?? null;

            // Validate OPD ID
            if ($opdId && !Opd::where('id', $opdId)->exists()) {
                $this->warn("Skipping user due to invalid OPD ID: {$opdId}");
                continue;
            }

            if (!$nip || !$email) {
                $this->warn("Skipping user due to missing NIP or Email: " . json_encode($userData));
                continue;
            }

            User::updateOrCreate(
                ['email' => $email],
                [
                    'nip' => $nip,
                    'name' => $name,
                    'opd_id' => $opdId,
                ]
            );
        }

        $this->info('User data import process completed.');
    }
}
