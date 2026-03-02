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
                $response = Http::withoutVerifying()
                    ->withUserAgent('Laravel/Cliente-API')
                    ->timeout(5)
                    ->get("https://viacep.com.br/ws/{$cleanCep}/json/");

                $data = $response->json();

                if ($response->successful() && is_array($data)) {
                    if (!isset($data['erro'])) {
                        return [
                            'street' => $data['logradouro'] ?? '',
                            'neighborhood' => $data['bairro'] ?? '',
                            'city' => $data['localidade'] ?? '',
                            'state' => $data['uf'] ?? '',
                        ];
                    }

                    Log::warning("ViaCEP: CEP não encontrado na base deles - {$cleanCep}");
                    return null;
                }

                Log::error("ViaCEP falhou ou bloqueou a requisição. Status: {$response->status()} | Body: {$response->body()}");
                return null;

            } catch (\Exception $e) {
                Log::error("Erro ao consultar ViaCEP para o CEP {$cleanCep}: " . $e->getMessage());
                return null;
            }
        });
    }
}