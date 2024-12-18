<?php

namespace timolake\LivewireTables\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use timolake\LivewireTables\Models\Post;
use timolake\LivewireTables\Models\User;

class TagFactory extends Factory
{
    public function definition(): array
    {
        return [
            "name" => $this->faker->word
        ];
    }
}
