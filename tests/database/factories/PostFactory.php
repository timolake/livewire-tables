<?php

namespace timolake\LivewireTables\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use timolake\LivewireTables\Tests\Models\Comment;
use timolake\LivewireTables\Tests\Models\Post;
use timolake\LivewireTables\Tests\Models\User;

class PostFactory extends Factory
{

    public function definition(): array
    {

        return [
            "user_id" => User::factory()->create(),
            "title" => $this->faker->sentence,
            "body" => $this->faker->paragraph,
        ];
    }

    public function withComments()
    {
        return $this->state(function (array $attributes) {
            return [];
        })->afterMaking(function (Post $post) {
            // ...
        })->afterCreating(function (Post $post) {
            Comment::factory()->count(3)->create([
                "post_id" => $post->id,
            ]);
        });
    }
    public function withTags(array $tags)
    {
        return $this->state(function (array $attributes) {
            return [];
        })->afterMaking(function (Post $post) {
            // ...
        })->afterCreating(function (Post $post) use ($tags) {
               $post->tags()->sync($tags);
        });
    }

    public function withFoobazComment()
    {
        return $this->state(function (array $attributes) {
            return [];

        })->afterMaking(function (Post $post) {
            // ...
        })->afterCreating(function (Post $post) {
            Comment::factory()->create([
                "post_id" => $post->id,
                "body" => "foobaz"
            ]);
        });
    }

    
}
