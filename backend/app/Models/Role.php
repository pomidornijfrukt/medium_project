<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $primaryKey = 'RoleName';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'RoleName',
        'RoleDescription'
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'Role', 'RoleName');
    }

    public function roleChanges()
    {
        return $this->hasMany(RoleChange::class, 'OldRoleID', 'RoleName');
    }
}