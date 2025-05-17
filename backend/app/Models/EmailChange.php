<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailChange extends Model
{
    protected $primaryKey = 'EmailChangeID';
    public $timestamps = false;

    protected $fillable = ['Old_e_mail', 'New_e_mail'];
}
