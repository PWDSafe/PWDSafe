<?php

namespace App\Http\Controllers\Api;

use App\AuditLog;
use App\Http\Controllers\Controller;
use App\Services\Auth\UserAuthenticator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API token ("trusted device") authentication for the browser extension and CLI.
 * Vault decryption happens entirely client-side; this only verifies the login
 * credentials and 2FA, then issues a Sanctum personal access token.
 */
class AuthController extends Controller
{
    public function __construct(private readonly UserAuthenticator $authenticator)
    {
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
            'totp_code' => ['nullable', 'string'],
        ]);

        $user = $this->authenticator->attempt($request->string('email')->toString(), $request->string('password')->toString());

        if (!$user) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if ($this->authenticator->requiresTwoFactor($user)) {
            $totpCode = $request->string('totp_code')->toString();

            if ($totpCode === '' || !$this->authenticator->verifyTwoFactorCode($user, $totpCode)) {
                return response()->json(['needs_2fa' => true], 422);
            }
        }

        $token = $user->createToken($request->string('device_name')->toString());

        AuditLog::logLogin($user, $request);

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'primarygroup' => $user->primarygroup,
                'uses_login_hash' => (bool) $user->uses_login_hash,
                'separate_vault_password' => $user->hasSeparateVaultPassword(),
            ],
            'vault_data' => [
                'encrypted_privkey' => $user->privkey,
                'salt' => $user->privkey_salt,
                'pubkey' => $user->pubkey,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json(['status' => 'OK']);
    }

    public function devices(Request $request): JsonResponse
    {
        $currentTokenId = $request->user()->currentAccessToken()?->id;

        $devices = $request->user()->tokens()
            ->orderByDesc('last_used_at')
            ->get(['id', 'name', 'last_used_at', 'created_at'])
            ->map(fn ($token) => [
                'id' => $token->id,
                'name' => $token->name,
                'last_used_at' => $token->last_used_at,
                'created_at' => $token->created_at,
                'is_current' => $token->id === $currentTokenId,
            ]);

        return response()->json($devices);
    }

    public function revokeDevice(Request $request, int $tokenId): JsonResponse
    {
        $deleted = $request->user()->tokens()->where('id', $tokenId)->delete();

        if ($deleted === 0) {
            return response()->json(['message' => 'Device not found'], 404);
        }

        return response()->json(['status' => 'OK']);
    }
}
