<?php
namespace App\Http\Controllers;

use App\Credential;
use App\Encryptedcredential;
use App\Helpers\Encryption;

class PasswordForController extends Controller
{
    public function index(Credential $credential)
    {
        $this->authorize('view', $credential);

        $pwd = Encryptedcredential::where('credentialid', $credential->id)->where('userid', auth()->user()->id)->firstOrFail();

        if ($pwd) {
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
}
