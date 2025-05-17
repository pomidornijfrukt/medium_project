<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Roles extends Model
{
    protected $primaryKey = 'Role_Name';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['Role_Name', 'Role_Description'];

    public function users()
    {
        return $this->hasMany(User::class, 'Role', 'Role_Name');
    }
}