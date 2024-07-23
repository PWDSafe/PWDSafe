<?php

namespace App;

use Database\Factories\GroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $users_count
 */
class Group extends Eloquent
{
    /** @use HasFactory<GroupFactory> */
    use HasFactory;

    protected $guarded = [];

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
        return $this->belongsToMany(User::class, 'usergroups', 'groupid', 'userid')
            ->orderBy('email')
            ->withPivot('permission');
    }

    public function userCountWithoutCurrentUser(): int
    {
        return $this->users()->where('userid', '!=', auth()->user()->id)->count();
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
