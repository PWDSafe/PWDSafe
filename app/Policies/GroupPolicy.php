<?php

namespace App\Policies;

use App\Group;
use App\SystemSetting;
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

        if ($group->children()->exists()) {
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
        if (!$user->groups->contains('id', $group->id)) {
            return false;
        }

        return $user->groups->find($group->id)->getRelationValue('pivot')->permission === 'admin' && $group->id !== $user->primarygroup;
    }

    /**
     * Determine whether the user can manage members (share) the group.
     *
     * @param  \App\User  $user
     * @param  \App\Group  $group
     * @return mixed
     */
    public function manageMembers(User $user, Group $group)
    {
        if ($group->id === $user->primarygroup) {
            return false;
        }

        if ($group->isInPrivateTree($user) && ! SystemSetting::get('private_groups_shareable', false)) {
            return false;
        }

        if (! $user->groups->contains('id', $group->id)) {
            return false;
        }

        return $user->groups->find($group->id)->getRelationValue('pivot')->permission === 'admin';
    }

    /**
     * Determine whether the user can create a sub-group under this group.
     *
     * @param  \App\User  $user
     * @param  \App\Group  $parentGroup
     * @return mixed
     */
    public function createSubGroup(User $user, Group $parentGroup)
    {
        if (!$user->groups->contains('id', $parentGroup->id)) {
            return false;
        }

        return $user->groups->find($parentGroup->id)->getRelationValue('pivot')->permission === 'admin';
    }
}
