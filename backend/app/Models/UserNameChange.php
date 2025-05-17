<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNameChange extends Model
{
    protected $primaryKey = 'UserNameChangeID';
    public $timestamps = false;

    protected $fillable = ['Old_UserName', 'New_UserName'];
}
