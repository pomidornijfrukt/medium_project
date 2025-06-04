<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    protected $primaryKey = 'TagName';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'TagName',
        'Description'
    ];

    /**
     * Boot the model and set up event listeners.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-fill description with TagName if not provided
        static::creating(function ($tag) {
            if (empty($tag->Description)) {
                $tag->Description = $tag->TagName;
            }
        });

        static::updating(function ($tag) {
            if (empty($tag->Description)) {
                $tag->Description = $tag->TagName;
            }
        });
    }

    public function posts()
    {
        return $this->belongsToMany(Post::class, 'tag_is_used', 'TagName', 'PostID')
            ->withTimestamps()
            ->using(TagIsUsed::class);
    }
}