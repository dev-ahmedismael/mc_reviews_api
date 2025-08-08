<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    protected $fillable = ['branch_id', 'post'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
