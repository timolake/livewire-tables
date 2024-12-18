<?php

namespace timolake\LivewireTables\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Color extends Model
{

    use HasFactory;

    public function user():belongsTo
    {
        return $this->belongsTo(User::class);
    }

}