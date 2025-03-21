<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Opd;

class ImportOpdsFromAPI extends Command
{
    protected $signature = 'opds:import';
    protected $description = 'Import OPD IDs and names from the external API';

    public function handle()
    {
        $apiUrl = 'https://api-splp.layanan.go.id/simpeg/1.0/opd?';
        $apiKey = config('app.api_keys.splp');

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'ApiKey' => $apiKey,
        ])->get($apiUrl);

        if ($response->failed()) {
            $this->error('Failed to fetch OPDs from API.');
            $this->error('API Response: ' . $response->body());
            return;
        }

        $data = $response->json();

        if (!isset($data['result']) || !is_array($data['result'])) {
            $this->error('Invalid API response format.');
            return;
        }

        foreach ($data['result'] as $opdData) {
            $kodeOpd = $opdData['kode_opd'] ?? null;
            $nama = $opdData['nama'] ?? null;

            if (!$kodeOpd || !$nama) {
                $this->warn("Skipping OPD due to missing data: " . json_encode($opdData));
                continue;
            }

            Opd::updateOrCreate(
                ['id' => $kodeOpd],
                ['name' => $nama]
            );
        }

        $this->info('OPDs imported successfully!');
    }
}
