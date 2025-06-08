<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $primaryKey = 'PostID';
    protected $fillable = [
        'Author',
        'Topic',
        'Content',
        'Status',
        'LastEditedAt',
        'ParentPostID',
        'PostType'
    ];

    protected $casts = [
        'Creation Date' => 'datetime',
        'LastEditedAt' => 'datetime'
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'Author', 'UID');
    }    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'tag_is_used', 'PostID', 'TagName')
            ->withTimestamps()
            ->using(TagIsUsed::class);
    }
    
    /**
     * Get the parent post if this is a linked post/comment.
     */
    public function parent()
    {
        return $this->belongsTo(Post::class, 'ParentPostID', 'PostID');
    }
    
    /**
     * Get the linked posts/comments for this post.
     */
    public function linkedPosts()
    {
        return $this->hasMany(Post::class, 'ParentPostID', 'PostID')
            ->where('Status', 'published')
            ->where('PostType', 'linked')
            ->orderBy('created_at', 'asc');
    }
    
    /**
     * Get all linked posts/comments for this post (including non-published ones).
     * Useful for admin/author functionality.
     */
    public function allLinkedPosts()
    {
        return $this->hasMany(Post::class, 'ParentPostID', 'PostID')
            ->orderBy('created_at', 'asc');
    }
}