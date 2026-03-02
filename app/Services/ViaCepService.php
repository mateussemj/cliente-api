<?php

namespace App\Services;

use App\Interfaces\AddressProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ViaCepService implements AddressProviderInterface
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
                $response = Http::timeout(5)->get("https://viacep.com.br/ws/{$cleanCep}/json/");

                if ($response->successful() && !isset($response['erro'])) {
                    return [
                        'street' => $response['logradouro'],
                        'neighborhood' => $response['bairro'],
                        'city' => $response['localidade'],
                        'state' => $response['uf'],
                    ];
                }

                Log::warning("ViaCEP: CEP não encontrado - {$cleanCep}");
                return null;

            } catch (\Exception $e) {
                Log::error("Erro ao consultar ViaCEP para o CEP {$cleanCep}: " . $e->getMessage());
                return null;
            }
        });
    }
}