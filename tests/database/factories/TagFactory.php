<?php

namespace timolake\LivewireTables\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    public function definition(): array
    {
        return [
            "name" => $this->faker->word
        ];
    }
}
