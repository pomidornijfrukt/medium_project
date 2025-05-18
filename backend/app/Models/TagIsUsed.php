<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TagIsUsed extends Pivot
{
    protected $table = 'Tag is Used';
    protected $primaryKey = 'UniqueID';
    
    protected $fillable = [
        'TagName',
        'PostID'
    ];
}