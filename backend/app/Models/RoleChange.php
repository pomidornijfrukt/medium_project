<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleChange extends Model
{
    use HasFactory;

    protected $primaryKey = 'RoleChangeID';
    protected $fillable = [
        'Old Role ID',
        'New Role ID'
    ];

    public function action()
    {
        return $this->hasOne(Action::class, 'RoleChangeID');
    }

    public function oldRole()
    {
        return $this->belongsTo(Role::class, 'Old Role ID', 'Role Name');
    }

    public function newRole()
    {
        return $this->belongsTo(Role::class, 'New Role ID', 'Role Name');
    }
}