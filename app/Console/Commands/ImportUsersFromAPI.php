<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\Opd;

class ImportUsersFromAPI extends Command
{
    protected $signature = 'users:import';
    protected $description = 'Import users dynamically for each OPD from the external API';

    public function handle()
    {
        $apiBaseUrl = 'https://api-splp.layanan.go.id/simpeg/1.0/pns_unker?';
        $apiKey = env('SPLP_API_KEY');
        $limit = 10000;

        $opds = Opd::all();

        if ($opds->isEmpty()) {
            $this->error('No OPDs found.');
            return;
        }

        foreach ($opds as $opd) {
            $this->info("Importing users for OPD ID: {$opd->id}");

            $offset = 0;
            $hasMoreData = true;

            while ($hasMoreData) {
                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                ])->get($apiBaseUrl, [
                    'apikey' => $apiKey,
                    'kode_unker' => $opd->id,
                    'offset' => $offset,
                    'limit' => $limit,
                ]);

                // ✅ Check if API request failed
                if ($response->failed()) {
                    $this->error("Failed to fetch users for OPD ID: {$opd->id}");
                    $this->error("API Response: " . $response->body());

                    // ✅ Handle authentication errors (error_code 1304)
                    $errorData = $response->json();
                    if (isset($errorData['error_code']) && $errorData['error_code'] == 1304) {
                        $this->error("Authentication failed! Please check your API key.");
                        return; // ✅ Stop execution to avoid further failures
                    }

                    break;
                }

                $data = $response->json();

                // ✅ Validate API response format
                if (!isset($data['result']) || !is_array($data['result'])) {
                    $this->error("Invalid API response format for OPD ID: {$opd->id}");
                    break;
                }

                $users = $data['result'];

                if (empty($users)) {
                    $this->info("No more users found for OPD ID: {$opd->id}");
                    break;
                }

                foreach ($users as $userData) {
                    $nip = $userData['nip'] ?? null;
                    $name = trim($userData['nama_lengkap'] ?? '');
                    $email = isset($userData['email']) ? trim($userData['email']) : null;
                    $opdId = $opd->id;

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

                if (count($users) < $limit) {
                    $this->info("Finished importing users for OPD ID: {$opd->id}");
                    break;
                }

                $offset += $limit;
            }
        }

        $this->info('User data import process completed.');
    }
}
