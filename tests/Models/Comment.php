<?php

namespace timolake\LivewireTables\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    use HasFactory;

    public function post():BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user():BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}