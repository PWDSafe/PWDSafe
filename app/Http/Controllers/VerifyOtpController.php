<?php

namespace App\Http\Controllers;

use App\AuditLog;
use App\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use PragmaRX\Google2FAQRCode\Google2FA;

class VerifyOtpController extends Controller
{
    use AuthenticatesUsers;

    public function index(): Factory|View|Application
    {
        return view('auth.verifytwofa');
    }

    public function store(Request $request, Google2FA $google2fa): JsonResponse|RedirectResponse
    {
        if (
            session()->has('username') &&
            (session()->has('vault_key') || session()->has('vault_unlocked') || session()->has('vault_unlock_pending')) &&
            auth()->guest() &&
            $request->has('twofacode')
        ) {
            $user = User::where('email', session()->get('username'))->first();
            if ($google2fa->verify($request->get('twofacode'), decrypt($user->two_factor_secret))) {
                auth()->loginUsingId($user->id);
                AuditLog::logLogin($user, $request);

                if ($request->hasSession()) {
                    $request->session()->put('auth.password_confirmed_at', time());
                }

                return $this->sendLoginResponse($request);
            }
        }

        // Session has expired or was never set — missing username or vault state in session.
        if (!session()->has('username') || (!session()->has('vault_key') && !session()->has('vault_unlocked') && !session()->has('vault_unlock_pending'))) {
            return redirect()
                ->back()
                ->withErrors(['session_expired' => true]);
        }

        return redirect()
            ->back()
            ->withErrors([
                'twofacode' => 'The two factor authentication code failed'
            ]);
    }
}
