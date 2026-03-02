<?php

namespace App\Services;

use App\Interfaces\AddressProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BrasilApiService implements AddressProviderInterface
{
    public function getAddressByCep(string $cep): ?array
    {
        $cleanCep = preg_replace('/[^0-9]/', '', $cep);

        if (strlen($cleanCep) !== 8) {
            return null;
        }

        $cacheKey = "endereco_cep_{$cleanCep}";

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($cleanCep) {
            try {
                $response = Http::withoutVerifying()
                    ->timeout(5)
                    ->get("https://brasilapi.com.br/api/cep/v1/{$cleanCep}");

                if ($response->successful()) {
                    $data = $response->json();
                    
                    return [
                        'street' => $data['street'] ?? '',
                        'neighborhood' => $data['neighborhood'] ?? '',
                        'city' => $data['city'] ?? '',
                        'state' => $data['state'] ?? '',
                    ];
                }

                Log::error("BrasilAPI falhou ou não encontrou o CEP. Status: {$response->status()}");
                return null;

            } catch (\Exception $e) {
                Log::error("Erro ao consultar BrasilAPI para o CEP {$cleanCep}: " . $e->getMessage());
                return null;
            }
        });
    }
}