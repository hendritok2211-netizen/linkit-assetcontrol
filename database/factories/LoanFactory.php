<?php

namespace Database\Factories;

use App\Models\Laptop;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Loan>
 */
class LoanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'laptop_id' => Laptop::factory(),
            'user_id' => User::factory(),
            'issued_by' => User::factory(['role' => 'admin', 'is_approved' => true]),
            'checked_out_at' => now(),
            'status' => 'active',
            'condition_out' => 'good',
            'handover_notes' => null,
        ];
    }
}
