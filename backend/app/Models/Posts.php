<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $primaryKey = 'PostID';
    public $timestamps = false;

    protected $fillable = ['Author', 'Topic', 'Content', 'Creation_date'];

    public function author()
    {
        return $this->belongsTo(User::class, 'Author', 'UID');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'TagIsUsed', 'Post_ID', 'TagName');
    }
}