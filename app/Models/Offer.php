<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Offer extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['title', 'category_id'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('offers');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
