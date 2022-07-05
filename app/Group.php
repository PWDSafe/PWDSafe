<?php

namespace App;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Group extends Eloquent
{
    public $timestamps = false;

    public function credentials()
    {
        return $this->hasMany(Credential::class, 'groupid');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'usergroups', 'groupid', 'userid');
    }

    public function usersWithoutCurrentUser()
    {
        return $this->users()->where('userid', '!=', auth()->user()->id);
    }

    public function deleteGroup()
    {
        $credentialids = \App\Credential::where('groupid', $this->id)->pluck('id');
        \App\Encryptedcredential::whereIn('credentialid', $credentialids)->delete();
        \App\Credential::where('groupid', $this->id)->delete();
        $this->users()->detach();
        $this->delete();
    }
}
