<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens; 

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $primaryKey = 'UID';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'UID',
        'Username',
        'Email',
        'Password',
        'Role',
        'Status',
        'LastLoginAt'
    ];

    protected $hidden = [
        'Password',
        'remember_token',
    ];

    protected $casts = [
        'LastLoginAt' => 'datetime',
        'CreatedAt' => 'datetime',
        'UpdatedAt' => 'datetime',
    ];

    // Automatically hash passwords
    public function setPasswordAttribute($value)
    {
        $this->attributes['Password'] = bcrypt($value);
    }


    public function role()
    {
        return $this->belongsTo(Role::class, 'Role', 'RoleName');
    }

    public function posts()
    {
        return $this->hasMany(Post::class, 'Author', 'UID');
    }

    public function actionsAsAuthor()
    {
        return $this->hasMany(Action::class, 'Author', 'UID');
    }

    public function actionsAsVictim()
    {
        return $this->hasMany(Action::class, 'Victim', 'UID');
    }

    public function usernameChanges()
    {
        return $this->hasMany(UserNameChange::class, 'UserNameChangeID', 'UID');
    }

    public function emailChanges()
    {
        return $this->hasMany(EmailChange::class, 'EmailChangeID', 'UID');
    }

    public function passwordChanges()
    {
        return $this->hasMany(PasswordChange::class, 'PassChangeID', 'UID');
    }

    public function roleChanges()
    {
        return $this->hasMany(RoleChange::class, 'RoleChangeID', 'UID');
    }
}