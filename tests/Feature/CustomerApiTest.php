<?php

use App\Models\Customer;
use App\Interfaces\AddressProviderInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\postJson;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

it('can create a customer and mock the external zip code API', function () {
    $this->mock(AddressProviderInterface::class, function ($mock) {
        $mock->shouldReceive('getAddressByCep')
             ->with('81280120')
             ->once()
             ->andReturn([ 
                 'street' => 'Rua Fictícia',
                 'neighborhood' => 'Bairro Teste',
                 'city' => 'Curitiba',
                 'state' => 'PR',
             ]);
    });

    $payload = [
        'name' => 'Mateus Teste',
        'email' => 'mateus@teste.com',
        'document' => '12345678901',
        'cep' => '81280120',
    ];

    postJson('/api/customers', $payload)
        ->assertStatus(201)
        ->assertJsonPath('street', 'Rua Fictícia')
        ->assertJsonPath('city', 'Curitiba');

    assertDatabaseHas('customers', [
        'email' => 'mateus@teste.com',
        'document' => '12345678901',
        'street' => 'Rua Fictícia',
        'city' => 'Curitiba',
    ]);
});