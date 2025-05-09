<?php

namespace timolake\LivewireTables\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use timolake\LivewireTables\Tests\Models\User;

class ColorFactory extends Factory
{
    public function definition(): array
    {
        return [
            "name" => $this->faker->colorName(),
            "user_id" => user::factory()->create(),
        ];
    }
}
