<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNameChange extends Model
{
    use HasFactory;

    protected $primaryKey = 'UserNameChangeID';
    protected $fillable = [
        'OldUserName',
        'NewUserName'
    ];

    public function action()
    {
        return $this->hasOne(Action::class, 'UserNameChangeID');
    }
}