<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Eloquent
{
    use HasFactory;

    public $timestamps = false;

    /**
     * @return HasMany<Credential>
     */
    public function credentials(): HasMany
    {
        return $this->hasMany(Credential::class, 'groupid');
    }

    /**
     * @return BelongsToMany<User>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'usergroups', 'groupid', 'userid');
    }

    /**
     * @return BelongsToMany<User>
     */
    public function usersWithoutCurrentUser(): BelongsToMany
    {
        return $this->users()->where('userid', '!=', auth()->user()->id);
    }

    public function deleteGroup(): void
    {
        $credentialids = Credential::where('groupid', $this->id)->pluck('id');
        Encryptedcredential::whereIn('credentialid', $credentialids)->delete();
        Credential::where('groupid', $this->id)->delete();
        $this->users()->detach();
        $this->delete();
    }
}
