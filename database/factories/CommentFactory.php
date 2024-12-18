<?php

namespace timolake\LivewireTables\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use timolake\LivewireTables\Models\Post;
use timolake\LivewireTables\Models\User;

class CommentFactory extends Factory
{
    public function definition(): array
    {
        return [
            "user_id" => User::factory()->create(),
            "body" => $this->faker->paragraph,
        ];
    }
}
