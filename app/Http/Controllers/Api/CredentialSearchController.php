<?php

namespace App\Http\Controllers\Api;

use App\Credential;
use App\Encryptedcredential;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Credential search and retrieval across all groups a user belongs to.
 * Used by the browser extension (autofill by domain) and CLI (search by term).
 * Decryption happens client-side; only ciphertext is returned.
 */
class CredentialSearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string'],
            'domain' => ['nullable', 'string'],
        ]);

        $q = trim($request->string('q')->toString());
        $domain = trim($request->string('domain')->toString());

        if ($q === '' && $domain === '') {
            return response()->json([]);
        }

        $groupIds = $request->user()->groups()->pluck('groups.id');

        $credentials = Credential::with('group:id,name')
            ->whereIn('groupid', $groupIds)
            ->when($q !== '', fn ($query) => $query->where(function ($sub) use ($q) {
                $sub->where('name', 'LIKE', "%{$q}%")
                    ->orWhere('username', 'LIKE', "%{$q}%")
                    ->orWhere('url', 'LIKE', "%{$q}%");
            }))
            ->when($domain !== '', fn ($query) => $query->where('url', 'LIKE', "%{$domain}%"))
            ->orderBy('name')
            ->limit(50)
            ->get();

        return response()->json($credentials);
    }

    public function show(Credential $credential): JsonResponse
    {
        $this->authorize('view', $credential);

        $pwd = Encryptedcredential::where('credentialid', $credential->id)
            ->where('userid', auth()->user()->id)
            ->firstOrFail();

        return response()->json([
            'id' => $credential->id,
            'name' => $credential->name,
            'url' => $credential->url,
            'username' => $credential->username,
            'notes' => $credential->notes,
            'groupid' => $credential->groupid,
            'data' => $pwd->data,
        ]);
    }
}
