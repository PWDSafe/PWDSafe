<?php

namespace App\Http\Controllers;

use App\Credential;
use App\Encryptedcredential;
use App\Group;
use App\Helpers\Encryption;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class ResetAccountController extends Controller
{
    public function index(): Factory|View|Application
    {
        abort_unless(
            config('ldap.enabled') && !auth()->user()->canDecryptPrivkey(session('password')),
            Response::HTTP_UNPROCESSABLE_ENTITY
        );

        return view('settings.resetaccount.index');
    }

    public function destroy(): RedirectResponse
    {
        abort_unless(
            config('ldap.enabled') && !auth()->user()->canDecryptPrivkey(session('password')),
            Response::HTTP_UNPROCESSABLE_ENTITY
        );

        $user = auth()->user();

        // Delete all stored credentials for user
        Credential::where('groupid', $user->primarygroup)->delete();

        // Remove group memberships, if user is only member, remove group as well
        $all_users_groups = $user
            ->groups()
            ->where('groupid', '!=', $user->primarygroup)
            ->get();

        $unshared_groups = $all_users_groups
            ->filter(fn ($row) => $row->users_count === 1)
            ->pluck('id');

        Group::whereIn('id', $unshared_groups)->delete();

        $shared_groups = $all_users_groups
            ->filter(fn ($row) => !in_array($row->id, $unshared_groups->toArray()))
            ->pluck('id');

        $user->groups()->detach($shared_groups);

        Encryptedcredential::where('userid', $user->id)->delete();

        // Regenerate private and public key
        $encryption = app(Encryption::class);
        [$privKey, $pubKey] = $encryption->genNewKeys();
        $user->pubkey = $pubKey;
        $user->privkey = $encryption->enc($privKey, session('password'));
        $user->save();

        return redirect()->route('group', $user->primarygroup);
    }
}
