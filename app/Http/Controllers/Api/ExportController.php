<?php

namespace App\Http\Controllers\Api;

use App\Group;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;

class ExportController extends Controller
{
    /**
     * Return all encrypted credentials for a group so the client can decrypt and download.
     */
    public function show(Group $group): Response|Application|ResponseFactory
    {
        abort_unless(auth()->user()->groups->contains('id', $group->id), 403);

        $credentials = $group->credentials()->withWhereHas('encryptedcredentials', function ($query) {
            $query->where('userid', auth()->user()->id);
        })->get();

        return response($credentials->map(fn ($credential) => [
            'id' => $credential->id,
            'name' => $credential->name,
            'url' => $credential->url,
            'username' => $credential->username,
            'notes' => $credential->notes,
            'data' => $credential->encryptedcredentials[0]->data,
        ]));
    }
}
