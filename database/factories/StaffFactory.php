<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Staff>
 */
class StaffFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'account' => $this->faker->unique()->userName,
            'email' => $this->faker->unique()->safeEmail,
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => \App\Models\Staff::ROLE_ADMIN,
        ];
    }
}
