<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use timolake\LivewireTables\Models\Color;
use timolake\LivewireTables\Models\Post;
use timolake\LivewireTables\Models\Tag;
use timolake\LivewireTables\Models\User;
use timolake\LivewireTables\Tests\tables\PostTable;

uses(RefreshDatabase::class);

test("that a table can search in a single table", function () {
   Post::factory()->count(5)->create([
       "title" => "foobar",
   ]);
    Post::factory()->count(4)->create([
        "title" => "baz",
    ]);
    Post::factory()->count(11)->create([
        "title" => "azerty",
    ]);

    $this->assertDatabaseCount("posts",20);

    Livewire::test(PostTable::class)
        ->set('search', 'foobar')
        ->call("query")
        ->assertCount('rowData',5);

    Livewire::test(PostTable::class)
        ->set('search', 'baz')
        ->call("query")
        ->assertCount('rowData',4);
});

test("that a table can search in a BelongsTo relation", function () {

    $user = User::factory()->create(["name" => "Jefke"]);
    Post::factory()->count(5)->create([
        "title" => "foobar",
    ]);
    Post::factory()->count(4)->create([
        "title" => "baz",
    ]);
    Post::factory()->count(11)->create([
        "title" => "azerty",
        "user_id" => $user->id,
    ]);

    $this->assertDatabaseCount("posts",20);

    Livewire::test(PostTable::class)
        ->set('search', "Jefke")
        ->call("query")
        ->assertCount('rowData',11);

});

test("that a table can search in a HasMany relation", function () {

    Post::factory()
        ->withComments()
        ->count(8)
        ->create(["title" => "azerty"]);

    Post::factory()
        ->withComments()
        ->count(5)
        ->withFoobazComment()
        ->create(["title" => "qwerty"]);

    Post::factory()
        ->count(3)
        ->create(["title" => "baz"]);

    $this->assertDatabaseCount("posts",16);

    Livewire::test(PostTable::class)
        ->set('search', "foobaz")
        ->call("query")
        ->assertCount('rowData',5);

    Livewire::test(PostTable::class)
        ->set('search', "baz")
        ->call("query")
        ->assertCount('rowData',8);

});

test("that a table can search in a BelongsToMany relation", function () {

    $tagFoo = Tag::factory()->create(["name" => "foo"]);
    $tagBar = Tag::factory()->create(["name" => "bar"]);
    $tagBaz = Tag::factory()->create(["name" => "baz"]);

    Post::factory()
        ->withTags([$tagFoo->id])
        ->create();

    Post::factory()
        ->withTags([$tagBar->id])
        ->create();

    Post::factory()
        ->withTags([$tagBaz->id])
        ->create();

    Post::factory()
        ->withTags([$tagFoo->id, $tagBar->id])
        ->create();

    Post::factory()
        ->count(4)
        ->create();



    $this->assertDatabaseCount("posts",8);

    Livewire::test(PostTable::class)
        ->set('search', "foo")
        ->call("query")
        ->assertCount('rowData',2);

    Livewire::test(PostTable::class)
        ->set('search', "bar")
        ->call("query")
        ->assertCount('rowData',2);

    Livewire::test(PostTable::class)
        ->set('search', "baz")
        ->call("query")
        ->assertCount('rowData',1);


});

test("that a table can search in a HasOne relation 3 levels down ", function () {


    $userWithColorYellow = User::factory()->withColor("Yellow")->create(["name" => "Jef"]);
    $userWithColorRed = User::factory()->withColor("Red")->create(["name" => "Piet"]);
    $userWithColorBlue = User::factory()->withColor("Blue")->create(["name" => "Jan"]);

    Post::factory()
        ->count(2)
        ->create([
            "user_id" => $userWithColorYellow->id
        ]);

    Post::factory()
        ->count(3)
        ->create([
            "user_id" => $userWithColorRed->id
        ]);

    Post::factory()
        ->count(5)
        ->create([
            "user_id" => $userWithColorBlue->id
        ]);


    $this->assertDatabaseCount("posts",10);

    Livewire::test(PostTable::class)
        ->set('search', "Yellow jef")
        ->call("query")
        ->assertCount('rowData',2);

    Livewire::test(PostTable::class)
        ->set('search', "Red")
        ->call("query")
        ->assertCount('rowData',3);

    Livewire::test(PostTable::class)
        ->set('search', "Blue")
        ->call("query")
        ->assertCount('rowData',5);

    Livewire::test(PostTable::class)
        ->set('search', "Blue jef")
        ->call("query")
        ->assertCount('rowData',0);


});
