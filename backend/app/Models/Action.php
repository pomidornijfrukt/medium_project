<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Action extends Model
{
    protected $primaryKey = 'ActionID';
    public $timestamps = false;

    protected $fillable = [
        'Author',
        'Victim',
        'Action_DateTime',
        'UserNameChangeID',
        'EmailChangeID',
        'PassChangeID',
        'RoleChangeID',
    ];

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
        return $this->belongsTo(PassChange::class, 'PassChangeID');
    }

    public function roleChange()
    {
        return $this->belongsTo(RoleChange::class, 'RoleChangeID');
    }
}
