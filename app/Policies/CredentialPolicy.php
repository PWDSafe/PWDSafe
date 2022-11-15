<?php

namespace App\Policies;

use App\Credential;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CredentialPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the credential.
     *
     * @param  \App\User  $user
     * @param  \App\Credential  $credential
     * @return mixed
     */
    public function view(User $user, Credential $credential)
    {
        return $user->groups->contains('id', $credential->groupid);
    }

    /**
     * Determine whether the user can update the credential.
     *
     * @param  \App\User  $user
     * @param  \App\Credential  $credential
     * @return mixed
     */
    public function update(User $user, Credential $credential)
    {
        if (!$user->groups->contains('id', $credential->groupid)) {
            return false;
        }

        return in_array($user->groups->find($credential->groupid)->pivot->permission, ['admin', 'write']);
    }

    /**
     * Determine whether the user can delete the credential.
     *
     * @param  \App\User  $user
     * @param  \App\Credential  $credential
     * @return mixed
     */
    public function delete(User $user, Credential $credential)
    {
        if (!$user->groups->contains('id', $credential->groupid)) {
            return false;
        }

        return in_array($user->groups->find($credential->groupid)->pivot->permission, ['admin', 'write']);
    }
}
