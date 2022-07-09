<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use PragmaRX\Google2FAQRCode\Google2FA;

class VerifyOtpController extends Controller
{
    use AuthenticatesUsers;

    public function index()
    {
        return view('auth.verifytwofa');
    }

    public function store(Request $request, Google2FA $google2fa)
    {
        if (
            session()->has('username') &&
            session()->has('password') &&
            auth()->guest() &&
            $request->has('twofacode')
        ) {
            $user = User::where('email', session()->get('username'))->first();
            if ($google2fa->verify($request->get('twofacode'), $user->two_factor_secret)) {
                auth()->loginUsingId($user->id);

                if ($request->hasSession()) {
                    $request->session()->put('auth.password_confirmed_at', time());
                }

                return $this->sendLoginResponse($request);
            }
        }

        return redirect()
            ->back()
            ->withErrors([
                'twofacode' => 'The two factor authentication code failed'
            ]);
    }
}
