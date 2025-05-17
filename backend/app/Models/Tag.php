<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $primaryKey = 'TagName';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['TagName'];

    public function posts()
    {
        return $this->belongsToMany(Post::class, 'TagIsUsed', 'TagName', 'Post_ID');
    }
}