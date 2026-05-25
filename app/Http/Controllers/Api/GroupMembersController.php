<?php

namespace App\Http\Controllers\Api;

use App\Encryptedcredential;
use App\Group;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class GroupMembersController extends Controller
{
    /**
     * Validate the user-to-add and return their public key plus the admin's
     * encrypted credential ciphertexts. The user is NOT yet added to the group.
     * The client uses this data to re-encrypt each credential for the new member.
     */
    public function prepare(Group $group, Request $request): Response|Application|ResponseFactory
    {
        $this->authorize('administer', $group);

        $params = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'permission' => ['required', Rule::in(['read', 'write', 'admin'])],
        ]);

        $newMember = User::findOrFail($params['user_id']);

        if ($newMember->groups->contains('id', $group->id)) {
            return response(['errors' => ['user_id' => ['User is already a member of this group']]], 422);
        }

        $credentials = Encryptedcredential::query()
            ->join('credentials', 'credentials.id', '=', 'encryptedcredentials.credentialid')
            ->where('credentials.groupid', $group->id)
            ->where('encryptedcredentials.userid', auth()->id())
            ->get(['encryptedcredentials.credentialid', 'encryptedcredentials.data']);

        return response([
            'user' => [
                'id' => $newMember->id,
                'pubkey' => $newMember->pubkey,
            ],
            'credentials' => $credentials->map(fn ($c) => [
                'id' => $c->credentialid,
                'data' => $c->data,
            ]),
        ]);
    }

    /**
     * Attach the user to the group and store the client-encrypted credentials for them.
     */
    public function confirm(Group $group, Request $request): Response|Application|ResponseFactory
    {
        $this->authorize('administer', $group);

        $params = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'permission' => ['required', Rule::in(['read', 'write', 'admin'])],
            'encrypted' => 'present|array',
            'encrypted.*.credentialid' => 'required|integer|exists:credentials,id',
            'encrypted.*.data' => 'required|string',
        ]);

        $newMember = User::findOrFail($params['user_id']);

        abort_if(auth()->user()->is($newMember), 403);

        if (!$newMember->groups->contains('id', $group->id)) {
            $newMember->groups()->attach($group, ['permission' => $params['permission']]);
        }

        foreach ($params['encrypted'] as $entry) {
            Encryptedcredential::updateOrCreate(
                ['credentialid' => $entry['credentialid'], 'userid' => $newMember->id],
                ['data' => $entry['data']]
            );
        }

        return response(['status' => 'OK']);
    }
}
