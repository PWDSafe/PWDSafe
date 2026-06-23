<?php

namespace App\Http\Controllers;

use App\Credential;
use App\Encryptedcredential;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;

class PasswordForController extends Controller
{
    public function index(Credential $credential): Response|Application|ResponseFactory
    {
        $this->authorize('view', $credential);

        $pwd = Encryptedcredential::where('credentialid', $credential->id)->where('userid', auth()->user()->id)->firstOrFail();

        return response([
            'status' => 'OK',
            'data' => $pwd->data,
            'totp_secret' => $pwd->totp_secret,
            'has_totp' => $credential->has_totp,
            'user' => $credential->username,
            'name' => $credential->name,
            'url' => $credential->url,
            'notes' => $credential->notes,
            'groupid' => $credential->groupid,
        ]);
    }
}
