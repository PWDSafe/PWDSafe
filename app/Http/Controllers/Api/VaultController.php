<?php

namespace App\Http\Controllers\Api;

use App\Credential;
use App\Encryptedcredential;
use App\Group;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * API endpoints for vault key material and vault lifecycle operations.
 * All credential decryption happens client-side; the server never sees plaintext passwords or private keys.
 */
class VaultController extends Controller
{
    /**
     * Return the KDF parameters needed to compute the login hash before authentication.
     * Called by login.js before the login POST so the client can derive vault_key → login_hash.
     * Guest-accessible — returns a deterministic fake salt for unknown emails so account
     * existence cannot be inferred from the response.
     */
    public function preflight(Request $request): JsonResponse
    {
        $email = $request->string('email')->trim()->toString();
        $user = User::where('email', $email)->first();

        if ($user) {
            return response()->json([
                'salt' => $user->privkey_salt,
                'uses_login_hash' => (bool) $user->uses_login_hash,
                'vault_configured' => $user->isVaultConfigured(),
                'separate_vault_password' => $user->hasSeparateVaultPassword(),
                'login_salt' => $user->login_salt,
            ]);
        }

        // Return a fake but consistent salt so an attacker cannot distinguish a real
        // account (non-null salt) from a non-existent one. The HMAC ties the fake salt
        // to the server secret, making it unpredictable without server access.
        $fakeSalt = hash_hmac('sha256', $email, config('app.key'));

        // In LDAP mode the raw password must reach the server so it can be forwarded to
        // the LDAP bind. Returning uses_login_hash=false makes the JS send the real password
        // instead of a derived hash, while keeping the fake salt for enumeration protection.
        return response()->json([
            'salt' => $fakeSalt,
            'uses_login_hash' => !config('ldap.enabled'),
            'vault_configured' => true,
            'separate_vault_password' => false,
            'login_salt' => null,
        ]);
    }

    /**
     * Return the encrypted private key and salt for the authenticated user.
     * The client uses this to derive the vault key and decrypt the private key locally.
     */
    public function keyData(): JsonResponse
    {
        $user = auth()->user();

        return response()->json([
            'encrypted_privkey' => $user->privkey,
            'salt' => $user->privkey_salt,
            'pubkey' => $user->pubkey,
        ]);
    }

    /**
     * Store the client-generated vault key material for a new or migrating LDAP user.
     * Sets vault_configured = true and separate_vault_password = true.
     */
    public function setup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'encrypted_privkey' => ['required', 'string'],
            'vault_salt' => ['required', 'string', 'size:64'],
            'pubkey' => ['required', 'string'],
            'login_hash' => ['nullable', 'string', 'size:64'],
        ]);

        $user = auth()->user();
        $user->privkey = $validated['encrypted_privkey'];
        $user->privkey_salt = $validated['vault_salt'];
        $user->pubkey = $validated['pubkey'];
        $user->vault_configured = true;

        if (! empty($validated['login_hash'])) {
            // Local user whose vault password becomes the login credential (same-password case).
            $user->password = Hash::make($validated['login_hash']);
            $user->separate_vault_password = false;
            $user->uses_login_hash = true;
        } else {
            // LDAP user: vault password is independent of the LDAP login credential.
            // Do not change uses_login_hash — the login credential is managed by LDAP.
            $user->separate_vault_password = true;
        }

        $user->save();

        session()->put('vault_unlocked', true);

        return response()->json([
            'redirect' => route('group', $user->primarygroup),
        ]);
    }

    /**
     * Save a re-encrypted private key after the user has recovered access to their vault
     * by providing their previous safe password (e.g. after an admin password reset).
     *
     * No server-side key verification is performed here because the client already proved
     * knowledge of the old vault key by successfully decrypting the private key. The user
     * is authenticated, so saving their own re-encrypted key is safe.
     */
    public function recover(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'encrypted_privkey' => ['required', 'string'],
            'salt' => ['required', 'string', 'size:64'],
        ]);

        $user = auth()->user();
        $user->privkey = $validated['encrypted_privkey'];
        $user->privkey_salt = $validated['salt'];
        $user->save();

        session()->put('vault_unlocked', true);

        return response()->json([
            'redirect' => route('group', $user->primarygroup),
        ]);
    }

    /**
     * Confirm that the client has successfully unlocked the vault after separate-password auth.
     * Sets vault_unlocked in the session so the middleware lets the user through.
     */
    public function confirmUnlock(Request $request): JsonResponse
    {
        session()->put('vault_unlocked', true);

        $user = auth()->user();

        return response()->json([
            'redirect' => route('group', $user->primarygroup),
        ]);
    }

    /**
     * Wipe all credentials and reset vault state so the user can set a new safe password.
     * Used from the unlock page when the user has forgotten their safe password.
     */
    public function reset(): JsonResponse
    {
        $user = auth()->user();

        Credential::where('groupid', $user->primarygroup)->delete();

        $otherGroups = $user
            ->groups()
            ->where('groupid', '!=', $user->primarygroup)
            ->get();

        $unsharedGroupIds = $otherGroups
            ->filter(fn ($group) => $group->users_count === 1)
            ->pluck('id');

        Group::whereIn('id', $unsharedGroupIds)->delete();

        $sharedGroupIds = $otherGroups
            ->filter(fn ($group) => ! in_array($group->id, $unsharedGroupIds->toArray()))
            ->pluck('id');

        $user->groups()->detach($sharedGroupIds);

        Encryptedcredential::where('userid', $user->id)->delete();

        $user->privkey = null;
        $user->privkey_salt = null;
        $user->pubkey = null;
        $user->vault_configured = false;
        $user->separate_vault_password = false;
        $user->save();

        session()->forget(['vault_key', 'vault_unlocked', 'password']);

        return response()->json([
            'redirect' => route('vault.setup'),
        ]);
    }
}
