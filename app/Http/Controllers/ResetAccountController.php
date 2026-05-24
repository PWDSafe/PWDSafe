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
        $this->authorizeReset();

        return view('settings.resetaccount.index');
    }

    public function destroy(): RedirectResponse
    {
        $this->authorizeReset();

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

        if (config('ldap.enabled') && session()->has('password')) {
            // LDAP path: re-encrypt new keys with the LDAP password held in session.
            $encryption = app(Encryption::class);
            [$privKey, $pubKey] = $encryption->genNewKeys();
            $newSalt = bin2hex(random_bytes(32));
            $newVaultKey = Encryption::deriveVaultKey(session('password'), $newSalt);
            $user->pubkey = $pubKey;
            $user->privkey = $encryption->encV2($privKey, $newVaultKey);
            $user->privkey_salt = $newSalt;
            $user->save();

            session()->put('vault_key', bin2hex($newVaultKey));

            return redirect()->route('group', $user->primarygroup);
        }

        // Local path: wipe vault data and send user through the vault setup flow.
        $user->pubkey = null;
        $user->privkey = null;
        $user->privkey_salt = null;
        $user->vault_configured = false;
        $user->save();

        return redirect()->route('vault.setup');
    }

    /**
     * For v1/LDAP users the server can verify vault decryptability directly.
     * For v2 (login_hash) users the vault key never reaches the server, so we
     * cannot verify it — the client is responsible for detecting the failure and
     * routing the user here.
     */
    private function authorizeReset(): void
    {
        if (!auth()->user()->uses_login_hash) {
            abort_unless(!auth()->user()->canDecryptPrivkey(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
