<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PassChange extends Model
{
    protected $primaryKey = 'PassChangeID';
    public $timestamps = false;

    protected $fillable = ['Old_Password_Hash', 'New_Password_Hash'];
}
