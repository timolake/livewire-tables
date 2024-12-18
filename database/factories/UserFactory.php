<?php

namespace timolake\LivewireTables\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use timolake\LivewireTables\Models\Comment;
use timolake\LivewireTables\Models\Color;
use timolake\LivewireTables\Models\Post;
use timolake\LivewireTables\Models\User;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            "name" => $this->faker->name,
            "email" => $this->faker->email,
        ];
    }

    public function withColor(string $color)
    {
        return $this->state(function (array $attributes) use($color) {
            return [];

        })->afterMaking(function (User $user) {
            // ...
        })->afterCreating(function (User $user) use($color) {
            Color::factory()->create([
                "name" => $color,
                "user_id" => $user->id
            ]);
        });
    }
}
