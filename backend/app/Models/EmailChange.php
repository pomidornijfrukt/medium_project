<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailChange extends Model
{
    use HasFactory;

    protected $primaryKey = 'EmailChangeID';
    protected $fillable = [
        'OldEmail',
        'NewEmail'
    ];

    public function action()
    {
        return $this->hasOne(Action::class, 'EmailChangeID');
    }
}