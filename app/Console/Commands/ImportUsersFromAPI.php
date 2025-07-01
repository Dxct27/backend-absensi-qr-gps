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

                    // Skip if NIP or Name is missing
                    if (!$nip || !$name) {
                        $this->warn("Skipping user due to missing NIP or Name: " . json_encode($userData));
                        continue;
                    }

                    // Check if a user exists by NIP or Name
                    $existingUser = User::where('nip', $nip)
                        ->orWhere('name', $name)
                        ->first();

                    if ($existingUser) {
                        // Check for email conflict
                        $emailConflict = User::where('email', $email)
                            ->where('id', '!=', $existingUser->id)
                            ->exists();

                        if ($emailConflict) {
                            $this->warn("Email conflict detected for user: {$name} (NIP: {$nip}). Skipping update.");
                            continue;
                        }

                        // Update the existing user if the email or OPD has changed
                        if ($existingUser->email !== $email || $existingUser->opd_id !== $opdId) {
                            $this->info("Updating user: {$name} (NIP: {$nip})");
                            $existingUser->update([
                                'email' => $email,
                                'opd_id' => $opdId,
                            ]);
                        } else {
                            $this->info("User already exists: {$name} (NIP: {$nip})");
                        }
                    } else {
                        // Create a new user if no match is found
                        User::create([
                            'nip' => $nip,
                            'name' => $name,
                            'email' => $email,
                            'opd_id' => $opdId,
                        ]);
                        $this->info("Created new user: {$name} (NIP: {$nip})");
                    }
                }

                if (count($users) < $limit) {
                    $this->info("Finished importing users for OPD ID: {$opd->id}");
                    break;
                }

                $offset += $limit;
            }

            $counter++;

            if ($counter % 5 === 0) {
                $this->info("Processed 5 OPDs. Waiting 15 seconds before continuing...");
                sleep(15);
            }
        }

        $this->info('User data import process completed.');
    }
}
