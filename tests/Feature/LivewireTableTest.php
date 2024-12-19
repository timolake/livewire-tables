<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use timolake\LivewireTables\Tests\Models\Post;
use timolake\LivewireTables\Tests\Models\Tag;
use timolake\LivewireTables\Tests\Models\User;
use timolake\LivewireTables\Tests\Tables\PostTable;

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
        ->assertSeeText('foobar')
        ->assertDontSeeText('baz')
        ->assertDontSeeText('azerty')
        ->assertViewHas('rowData', function ($rowData) {
            return count($rowData) == 5;
        });

    Livewire::test(PostTable::class)
        ->set('search', 'baz')
        ->assertSeeText('baz')
        ->assertDontSeeText('foobar')
        ->assertDontSeeText('azerty')
        ->assertViewHas('rowData', function ($rowData) {
            return count($rowData) == 4;
        });
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
        ->set('search', 'Jefke')
        ->assertSeeText('Jefke')
        ->assertSeeText('azerty')
        ->assertDontSeeText('foobar')
        ->assertDontSeeText('baz')
        ->assertViewHas('rowData', function ($rowData) {
            return count($rowData) == 11;
        });

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
        ->set('search', 'foobaz')
        ->assertSeeText('foobaz')
        ->assertSeeText('qwerty')
        ->assertDontSeeText('azerty')
        ->assertDontSeeText('foobar')
        ->assertViewHas('rowData', function ($rowData) {
            return count($rowData) == 5;
        });

    Livewire::test(PostTable::class)
        ->set('search', 'baz')
        ->assertSeeText('baz')
        ->assertSeeText('foobaz')
        ->assertSeeText('qwerty')
        ->assertDontSeeText('azerty')
        ->assertDontSeeText('foobar')
        ->assertViewHas('rowData', function ($rowData) {
            return count($rowData) == 8;
        });

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
        ->set('search', 'foo')
        ->assertSeeText('foo')
        ->assertSeeText('bar')
        ->assertDontSeeText('baz')
        ->assertViewHas('rowData', function ($rowData) {
            return count($rowData) == 2;
        });

    Livewire::test(PostTable::class)
        ->set('search', 'bar')
        ->assertSeeText('bar')
        ->assertSeeText('foo')
        ->assertDontSeeText('baz')
        ->assertViewHas('rowData', function ($rowData) {
            return count($rowData) == 2;
        });

    Livewire::test(PostTable::class)
        ->set('search', 'baz')
        ->assertSeeText('baz')
        ->assertDontSeeText('foo')
        ->assertDontSeeText('bar')
        ->assertViewHas('rowData', function ($rowData) {
            return count($rowData) == 1;
        });

});

test("that a table can search in a HasOne relation 3 levels down ", function () {


    $userWithColorYellow = User::factory()->withColor("Yellow")->create(["name" => "Jef"]);
    $userWithColorRed = User::factory()->withColor("Red")->create(["name" => "Piet"]);
    $userWithColorBlue = User::factory()->withColor("Blue")->create(["name" => "Jan"]);

    Post::factory()
        ->count(2)

        ->create([
            "title" => "foobaz",
            "user_id" => $userWithColorYellow->id
        ]);

    Post::factory()
        ->count(3)
        ->withFoobazComment()
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
        ->set('search', 'Yellow Jef')
        ->assertSeeText('Jef')
        ->assertSeeText('Yellow')
        ->assertDontSeeText('Piet')
        ->assertDontSeeText('Jan')
        ->assertViewHas('rowData', function ($rowData) {
            return count($rowData) == 2;
        });

    Livewire::test(PostTable::class)
        ->set('search', 'Red')
        ->assertSeeText('Piet')
        ->assertDontSeeText('Jef')
        ->assertDontSeeText('Jan')
        ->assertViewHas('rowData', function ($rowData) {
            return count($rowData) == 3;
        });

    Livewire::test(PostTable::class)
        ->set('search', 'Blue')
        ->assertSeeText('Blue')
        ->assertSeeText('Jan')
        ->assertDontSeeText('Jef')
        ->assertDontSeeText('Piet')
        ->assertViewHas('rowData', function ($rowData) {
            return count($rowData) == 5;
        });

    Livewire::test(PostTable::class)
        ->set('search', 'foobaz')
        ->assertSeeText('foobaz')
        ->assertDontSeeText('jan')
        ->assertViewHas('rowData', function ($rowData) {
            ray($rowData);
            return count($rowData) == 5;
        });

    Livewire::test(PostTable::class)
        ->set('search', 'green marcel')
        ->assertDontSeeText('Jef')
        ->assertDontSeeText('Jan')
        ->assertDontSeeText('Piet')
        ->assertViewHas('rowData', function ($rowData) {
            return count($rowData) == 0;
        });



});
