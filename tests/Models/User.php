<?php

namespace timolake\LivewireTables\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Model
{

    use HasFactory;

    public function posts():HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function color(): HasOne
    {
        return $this->hasOne(Color::class);
    }

    public function comments():HasMany
    {
        return $this->hasMany(Comment::class);
    }
}