<?php

namespace App;

use Database\Factories\GroupFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $users_count
 * @property int $credentials_count
 * @property int $children_count
 * @property int|null $parent_id
 */
class Group extends Eloquent
{
    /** @use HasFactory<GroupFactory> */
    use HasFactory;

    protected $guarded = [];

    public $timestamps = false;

    /** @return HasMany<Credential, $this> */
    public function credentials(): HasMany
    {
        return $this->hasMany(Credential::class, 'groupid');
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'usergroups', 'groupid', 'userid')
            ->orderBy('email')
            ->withPivot('permission');
    }

    /** @return BelongsTo<Group, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'parent_id');
    }

    /** @return HasMany<Group, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(Group::class, 'parent_id');
    }

    /**
     * Returns the ancestor chain from root to this group's parent (for breadcrumbs).
     *
     * @return Collection<int, Group>
     */
    public function ancestors(): Collection
    {
        $ancestors = new Collection();
        $current = $this->parent;

        while ($current !== null) {
            $ancestors->prepend($current);
            $current = $current->parent;
        }

        return $ancestors;
    }

    public function isRootGroup(): bool
    {
        return $this->parent_id === null;
    }

    public function isInPrivateTree(User $user): bool
    {
        $current = $this;
        while ($current !== null) {
            if ($current->id === $user->primarygroup) {
                return true;
            }
            $current = $current->parent;
        }

        return false;
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
