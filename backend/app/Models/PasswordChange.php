<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordChange extends Model
{
    use HasFactory;

    protected $primaryKey = 'PassChangeID';
    protected $fillable = [
        'OldPasswordHash',
        'NewPasswordHash'
    ];

    public function action()
    {
        return $this->hasOne(Action::class, 'PassChangeID');
    }
}