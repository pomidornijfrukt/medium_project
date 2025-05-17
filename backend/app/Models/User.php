<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $primaryKey = 'UID';
    public $timestamps = false;

    protected $fillable = ['Username', 'Email', 'Password', 'Role'];

    public function role()
    {
        return $this->belongsTo(Roles::class, 'Role', 'Role_Name');
    }

    public function posts()
    {
        return $this->hasMany(Post::class, 'Author', 'UID');
    }

    public function authoredActions()
    {
        return $this->hasMany(Action::class, 'Author', 'UID');
    }

    public function victimActions()
    {
        return $this->hasMany(Action::class, 'Victim', 'UID');
    }
}