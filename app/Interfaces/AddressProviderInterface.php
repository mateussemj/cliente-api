<?php

namespace App\Interfaces;

interface AddressProviderInterface
{
    /**
     * Busca o endereço a partir do CEP.
     * Deve retornar um array com os dados ou null em caso de falha.
     */
    public function getAddressByCep(string $cep): ?array;
}