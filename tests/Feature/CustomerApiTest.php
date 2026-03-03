<?php

use App\Models\Customer;
use App\Interfaces\AddressProviderInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

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

it('can update a customer without changing the zip code', function () {
    $customer = Customer::factory()->create([
        'name' => 'Nome Antigo',
    ]);

    $payload = [
        'name' => 'Nome Atualizado',
        'email' => $customer->email,
        'document' => $customer->document,
        'cep' => $customer->cep,
    ];

    putJson("/api/customers/{$customer->id}", $payload)
        ->assertStatus(200)
        ->assertJsonPath('name', 'Nome Atualizado');

    assertDatabaseHas('customers', [
        'id' => $customer->id,
        'name' => 'Nome Atualizado',
    ]);
});

it('can update a customer and fetch new address when zip code changes', function () {
    $customer = Customer::factory()->create([
        'cep' => '01001000',
        'street' => 'Rua Original',
    ]);

    $this->mock(AddressProviderInterface::class, function ($mock) {
        $mock->shouldReceive('getAddressByCep')
             ->with('81280120')
             ->once()
             ->andReturn([ 
                 'street' => 'Nova Rua Atualizada',
                 'neighborhood' => 'Bairro Novo',
                 'city' => 'Curitiba',
                 'state' => 'PR',
             ]);
    });

    $payload = [
        'name' => $customer->name,
        'email' => $customer->email,
        'document' => $customer->document,
        'cep' => '81280120',
    ];

    putJson("/api/customers/{$customer->id}", $payload)
        ->assertStatus(200)
        ->assertJsonPath('street', 'Nova Rua Atualizada');

    assertDatabaseHas('customers', [
        'id' => $customer->id,
        'cep' => '81280120',
        'street' => 'Nova Rua Atualizada',
    ]);
});

it('can delete a customer', function () {
    $customer = Customer::factory()->create();

    deleteJson("/api/customers/{$customer->id}")
        ->assertStatus(204);

    assertDatabaseMissing('customers', [
        'id' => $customer->id,
    ]);
});