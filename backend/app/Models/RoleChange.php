<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleChange extends Model
{
    protected $primaryKey = 'RoleChangeID';
    public $timestamps = false;

    protected $fillable = ['Old_Role_ID', 'New_Role_ID'];
}
