<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class VaultSetupController extends Controller
{
    /**
     * Show the vault setup page for LDAP users (new and migrating).
     * When the user has an existing privkey that can be decrypted server-side
     * (migration path), the encrypted vault_key_hex is passed so the client can
     * re-encrypt the private key without server involvement.
     */
    public function show(): View
    {
        $user = auth()->user();
        $vaultKeyHex = session('migration_vault_key_hex');

        // For admin-created local users (raw password, no vault yet), vault setup should also
        // update the login credential so future logins use the vault password.
        $updateLoginHash = $user->auth_source !== 'ldap' && ! $user->uses_login_hash;

        return view('vault.setup', [
            'vaultKeyHex' => $vaultKeyHex,
            'encryptedPrivkey' => $user->privkey,
            'vaultSalt' => $user->privkey_salt,
            'pubkey' => $user->pubkey,
            'updateLoginHash' => $updateLoginHash,
        ]);
    }
}
