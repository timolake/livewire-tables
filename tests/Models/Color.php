<?php

namespace timolake\LivewireTables\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Color extends Model
{

    use HasFactory;

    public function user():belongsTo
    {
        return $this->belongsTo(User::class);
    }

}