<?php

namespace Database\Factories;

use App\Models\Division;
use App\Models\Laptop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Laptop>
 */
class LaptopFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asset_code' => 'LT-'.fake()->unique()->bothify('######'),
            'serial_number' => fake()->unique()->bothify('SN-########'),
            'brand' => fake()->randomElement(['Lenovo', 'Dell', 'HP', 'Asus']),
            'model' => fake()->randomElement(['ThinkPad E14', 'Latitude 5440', 'ProBook 440', 'ExpertBook B1']),
            'ram' => '16 GB',
            'storage_capacity' => '512 GB',
            'purchase_date' => fake()->date(),
            'division_id' => Division::factory(),
            'status' => 'available',
            'condition' => 'good',
            'notes' => null,
        ];
    }
}
