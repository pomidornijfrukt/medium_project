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

    public function posts()
    {
        return $this->belongsToMany(Post::class, 'Tag is Used', 'TagName', 'PostID')
            ->withTimestamps()
            ->using(TagIsUsed::class);
    }
}