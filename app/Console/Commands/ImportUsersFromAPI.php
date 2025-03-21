<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\Opd;

class ImportUsersFromAPI extends Command
{
    protected $signature = 'users:import {opdId?}';
    protected $description = 'Import users dynamically for each OPD or a specific OPD from the external API';

    public function handle()
    {
        $apiBaseUrl = 'https://api-splp.layanan.go.id/simpeg/1.0/pns_unker?';
        $apiKey = config('app.api_keys.splp');
        $limit = 10000;
        $opdId = $this->argument('opdId');

        if ($opdId) {
            $opds = Opd::where('id', $opdId)->get();
            if ($opds->isEmpty()) {
                $this->error("No OPD found with ID: {$opdId}");
                return;
            }
        } else {
            $opds = Opd::all();
            if ($opds->isEmpty()) {
                $this->error('No OPDs found.');
                return;
            }
        }

        $counter = 0;

        foreach ($opds as $opd) {
            $this->info("Importing users for OPD ID: {$opd->id}");

            $offset = 0;
            $hasMoreData = true;

            while ($hasMoreData) {
                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'ApiKey' => $apiKey,
                ])->get($apiBaseUrl, [
                            'kode_unker' => $opd->id,
                            'offset' => $offset,
                            'limit' => $limit,
                        ]);

                if ($response->failed()) {
                    $this->error("Failed to fetch users for OPD ID: {$opd->id}");
                    $this->error("API Response: " . $response->body());

                    $errorData = $response->json();
                    if (isset($errorData['error_code']) && $errorData['error_code'] == 1304) {
                        $this->error("Authentication failed! Please check your API key.");
                        return;
                    }

                    break;
                }

                $data = $response->json();

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

            $counter++;

            if (!$opdId && $counter % 10 === 0) {
                $this->info("Processed 10 OPDs. Waiting 1 minute before continuing...");
                sleep(60);
            }
        }

        $this->info('User data import process completed.');
    }
}
