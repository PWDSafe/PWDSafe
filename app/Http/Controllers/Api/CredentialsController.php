<?php

namespace App\Http\Controllers\Api;

use App\Credential;
use App\Encryptedcredential;
use App\Group;
use App\Http\Controllers\Controller;
use App\Http\Requests\MoveCredentialRequest;
use App\Http\Requests\StoreCredentialsRequest;
use Illuminate\Http\JsonResponse;

class CredentialsController extends Controller
{
    public function index(Group $group): JsonResponse
    {
        $this->authorize('view', $group);

        return response()->json(
            Credential::with('group:id,name')
                ->where('groupid', $group->id)
                ->orderBy('site')
                ->get()
        );
    }

    public function store(StoreCredentialsRequest $request, Group $group): JsonResponse
    {
        $params = $request->validated();

        $credential = Credential::create([
            'groupid' => $group->id,
            'site' => $params['site'],
            'username' => $params['user'],
            'notes' => $params['notes'] ?? null,
        ]);

        foreach ($params['encrypted'] as $entry) {
            Encryptedcredential::create([
                'credentialid' => $credential->id,
                'userid' => $entry['userid'],
                'data' => $entry['data'],
            ]);
        }

        return response()->json($credential, 201);
    }

    public function move(MoveCredentialRequest $request, Credential $credential): JsonResponse
    {
        $params = $request->validated();

        $destination = Group::findOrFail($params['group_id']);
        $this->authorize('update', $destination);

        $newCredential = Credential::create([
            'groupid' => $destination->id,
            'site' => $credential->site,
            'username' => $credential->username,
            'notes' => $credential->notes,
        ]);

        foreach ($params['encrypted'] as $entry) {
            Encryptedcredential::create([
                'credentialid' => $newCredential->id,
                'userid' => $entry['userid'],
                'data' => $entry['data'],
            ]);
        }

        $credential->deleteCredential();

        return response()->json($newCredential->load('group:id,name'), 201);
    }
}
