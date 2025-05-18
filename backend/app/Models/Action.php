<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Action extends Model
{
    use HasFactory;

    protected $primaryKey = 'ActionID';
    
    protected $fillable = [
        'Author',
        'Victim',
        'ActionDateTime',
        'UserNameChangeID',  // Only one of these
        'EmailChangeID',     // four should be set
        'PassChangeID',      // per action
        'RoleChangeID'
    ];

    protected $casts = [
        'ActionDateTime' => 'datetime'
    ];

    // Relationships
    public function author()
    {
        return $this->belongsTo(User::class, 'Author', 'UID');
    }

    public function victim()
    {
        return $this->belongsTo(User::class, 'Victim', 'UID');
    }

    public function usernameChange()
    {
        return $this->belongsTo(UserNameChange::class, 'UserNameChangeID');
    }

    public function emailChange()
    {
        return $this->belongsTo(EmailChange::class, 'EmailChangeID');
    }

    public function passwordChange()
    {
        return $this->belongsTo(PasswordChange::class, 'PassChangeID');
    }

    public function roleChange()
    {
        return $this->belongsTo(RoleChange::class, 'RoleChangeID');
    }

    // Helper to get the type of change
    public function getChangeTypeAttribute()
    {
        return match(true) {
            !is_null($this->UserNameChangeID) => 'username',
            !is_null($this->EmailChangeID) => 'email',
            !is_null($this->PassChangeID) => 'password',
            !is_null($this->RoleChangeID) => 'role',
            default => 'unknown'
        };
    }
}