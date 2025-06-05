<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleChange extends Model
{
    use HasFactory;

    protected $primaryKey = 'RoleChangeID';
    protected $fillable = [
        'OldRoleID',
        'NewRoleID'
    ];

    public function action()
    {
        return $this->hasOne(Action::class, 'RoleChangeID');
    }

    public function oldRole()
    {
        return $this->belongsTo(Role::class, 'OldRoleID', 'RoleName');
    }

    public function newRole()
    {
        return $this->belongsTo(Role::class, 'NewRoleID', 'RoleName');
    }
}