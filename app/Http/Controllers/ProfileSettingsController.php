<?php

namespace App\Http\Controllers;

use App\Helpers\Encryption;
use App\Helpers\LdapAuthentication;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class ProfileSettingsController extends Controller
{
    public function index(): Factory|View|Application
    {
        return view('settings', ['section' => 'profile']);
    }

    public function loginPassword(): Factory|View|Application
    {
        return view('settings', ['section' => 'login']);
    }

    public function safePassword(): Factory|View|Application
    {
        return view('settings', ['section' => 'vault']);
    }

    public function store(Request $request): RedirectResponse
    {
        $changeType = $request->input('change_type', 'profile');

        if (config('ldap.enabled')) {
            if ($changeType === 'profile') {
                return $this->handleProfileUpdate($request);
            }
            abort_if($changeType === 'login', Response::HTTP_FORBIDDEN);
            abort_if($changeType !== 'vault', Response::HTTP_FORBIDDEN);

            return $this->handleLdapVaultPasswordChange($request);
        }

        return match ($changeType) {
            'profile' => $this->handleProfileUpdate($request),
            'login' => $this->handleLoginPasswordChange($request),
            'vault' => $this->handleVaultPasswordChange($request),
            default => abort(Response::HTTP_UNPROCESSABLE_ENTITY),
        };
    }

    private function handleProfileUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        auth()->user()->update(['name' => $validated['name']]);

        return redirect()->back()->with('success', 'Profile updated successfully');
    }

    private function handleLdapVaultPasswordChange(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'oldpwd' => 'required|string',
            'password' => ['required', 'string', 'confirmed'],
        ]);

        $enc = app(Encryption::class);
        $user = auth()->user();

        $privatekey = $user->isV2Format()
            ? $enc->decV2($user->privkey, Encryption::deriveVaultKey($validated['oldpwd'], $user->privkey_salt))
            : $enc->dec($user->privkey, $validated['oldpwd']);

        if (strlen($privatekey) === 0) {
            return redirect()->back()->withErrors(['oldpwd' => 'Old password missmatch']);
        }

        if (!app(LdapAuthentication::class)->login($user->email, $validated['password'])) {
            return redirect()->back()->withErrors(['newpwd' => 'New password is incorrect when authenticating to LDAP/AD']);
        }

        $newSalt = bin2hex(random_bytes(32));
        $newVaultKey = Encryption::deriveVaultKey($validated['password'], $newSalt);
        $user->privkey = $enc->encV2($privatekey, $newVaultKey);
        $user->privkey_salt = $newSalt;
        $user->save();

        session()->put('vault_key', bin2hex($newVaultKey));

        return redirect()->to('/groups/' . $user->primarygroup);
    }

    /**
     * Section A: Change the login password only (independent of vault).
     */
    private function handleLoginPasswordChange(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $request->validate(['oldpwd' => 'required|string']);

        if (!Hash::check($request->input('oldpwd'), $user->password)) {
            return redirect()->back()->withErrors(['oldpwd' => 'Old password missmatch']);
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'confirmed'],
            'new_login_salt' => 'required|string|size:64',
        ]);

        $user->password = Hash::make($validated['password']);
        $user->login_salt = $validated['new_login_salt'];
        $user->separate_vault_password = true;
        $user->uses_login_hash = true;
        $user->save();

        return redirect()->back()->with('success', 'Login password changed successfully');
    }

    /**
     * Section B: Change the vault (safe) password — re-encrypts the private key client-side.
     */
    private function handleVaultPasswordChange(Request $request): RedirectResponse
    {
        $user = auth()->user();

        if ($user->hasSeparateVaultPassword()) {
            $request->validate(['oldpwd' => ['required', 'string', 'size:64']]);

            $enc = app(Encryption::class);
            $decrypted = $enc->decV2($user->privkey, hex2bin($request->input('oldpwd')));
            if (strlen($decrypted) === 0) {
                return redirect()->back()->withErrors(['oldpwd' => 'Incorrect safe password']);
            }

            $clientData = $request->validate([
                'new_encrypted_privkey' => 'required|string',
                'new_salt' => 'required|string|size:64',
            ]);

            $user->privkey = $clientData['new_encrypted_privkey'];
            $user->privkey_salt = $clientData['new_salt'];
            $user->save();

            return redirect()->back()->with('success', 'Safe password changed successfully');
        }

        $request->validate(['oldpwd' => 'required|string']);

        if (!Hash::check($request->input('oldpwd'), $user->password)) {
            return redirect()->back()->withErrors(['oldpwd' => 'Old password missmatch']);
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'confirmed'],
            'new_encrypted_privkey' => 'required|string',
            'new_salt' => 'required|string|size:64',
            'new_login_salt' => 'required|string|size:64',
        ]);

        $user->password = Hash::make($validated['password']);
        $user->privkey = $validated['new_encrypted_privkey'];
        $user->privkey_salt = $validated['new_salt'];
        $user->login_salt = $validated['new_login_salt'];
        $user->separate_vault_password = true;
        $user->uses_login_hash = true;
        $user->save();

        return redirect()->back()->with('success', 'Safe password changed successfully');
    }
}
