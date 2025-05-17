<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TagIsUsed extends Model
{
    protected $table = 'TagIsUsed';
    protected $primaryKey = 'UniqueID';
    public $timestamps = false;

    protected $fillable = ['TagName', 'Post_ID'];
}
