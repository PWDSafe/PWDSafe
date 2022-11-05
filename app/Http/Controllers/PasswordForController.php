<?php

namespace App\Http\Controllers;

use App\Credential;
use App\Encryptedcredential;
use App\Helpers\Encryption;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;

class PasswordForController extends Controller
{
    public function index(Credential $credential): Response|Application|ResponseFactory
    {
        $this->authorize('view', $credential);

        $pwd = Encryptedcredential::where('credentialid', $credential->id)->where('userid', auth()->user()->id)->firstOrFail();

        $encryption = app(Encryption::class);

        $pwddecoded = $encryption->decWithPriv(
            $pwd->data,
            $encryption->dec(auth()->user()->privkey, session()->get('password'))
        );

        return response([
            'status' => 'OK',
            'pwd' => $pwddecoded,
            'user' => $credential->username,
            'site' => $credential->site,
            'notes' => $credential->notes,
            'groupid' => $credential->groupid
        ]);
    }
}
