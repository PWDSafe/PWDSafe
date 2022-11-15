<?php

namespace App\Policies;

use App\Group;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GroupPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the group.
     *
     * @param  \App\User  $user
     * @param  \App\Group  $group
     * @return mixed
     */
    public function view(User $user, Group $group)
    {
        return $user->groups->contains('id', $group->id);
    }

    /**
     * Determine whether the user can update the group.
     *
     * @param  \App\User  $user
     * @param  \App\Group  $group
     * @return mixed
     */
    public function update(User $user, Group $group)
    {
        if (!$user->groups->contains('id', $group->id)) {
            return false;
        }

        return in_array($user->groups->find($group->id)->getRelationValue('pivot')->permission, ['admin', 'write']);
    }

    /**
     * Determine whether the user can delete the group.
     *
     * @param  \App\User  $user
     * @param  \App\Group  $group
     * @return mixed
     */
    public function delete(User $user, Group $group)
    {
        if (!$user->groups->contains('id', $group->id)) {
            return false;
        }

        return $user->groups->find($group->id)->getRelationValue('pivot')->permission === 'admin' && $group->id !== $user->primarygroup;
    }

    /**
     * Determine whether the user can administer the group.
     *
     * @param  \App\User  $user
     * @param  \App\Group  $group
     * @return mixed
     */
    public function administer(User $user, Group $group)
    {
        return $user->groups->find($group->id)->getRelationValue('pivot')->permission === 'admin' && $group->id !== $user->primarygroup;
    }
}
