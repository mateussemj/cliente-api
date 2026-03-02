<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'document' => fake()->unique()->numerify('###########'),
            'cep' => '81260000', 
            'street' => 'Avenida Juscelino Kubitcheck de Oliveira',
            'neighborhood' => 'CIC',
            'city' => 'Curitiba',
            'state' => 'PR',
        ];
    }
}
