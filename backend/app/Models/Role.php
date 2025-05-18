<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $primaryKey = 'Role Name';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'Role Name',
        'Role Description'
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'Role', 'Role Name');
    }

    public function roleChanges()
    {
        return $this->hasMany(RoleChange::class, 'Old Role ID', 'Role Name');
    }
}