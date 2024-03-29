<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\LdapAuthentication;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FAQRCode\Google2FA;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function login(Request $request, Google2FA $google2fa): JsonResponse|Redirector|RedirectResponse|Application|Response
    {
        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if (method_exists($this, 'hasTooManyLoginAttempts') &&
            $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            // If user has two fa enabled, logout and redirect to verify
            if (!is_null(auth()->user()->two_factor_secret)) {
                session()->put('username', auth()->user()->email);
                auth()->logout();

                return redirect('verifyotp');
            }

            if ($request->hasSession()) {
                $request->session()->put('auth.password_confirmed_at', time());
            }

            if (!auth()->user()->canDecryptPrivkey(session('password'))) {
                return redirect()->route('changepassword');
            }

            return $this->sendLoginResponse($request);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

    protected function attemptLogin(Request $request): bool
    {
        $credentials = $request->only('email', 'password');

        if (!config('ldap.enabled') && Auth::attempt($credentials)) {
            session()->put('password', $credentials['password']);

            return true;
        } elseif (config('ldap.enabled') && app(LdapAuthentication::class)->login($credentials['email'], $credentials['password'])) {
            $user = User::where('email', $credentials['email'])->first();

            if (!$user) {
                User::registerUser($credentials['email'], $credentials['password']);
                $user = User::where('email', $credentials['email'])->first();
            }

            Auth::loginUsingId($user->id);
            session()->put('password', $credentials['password']);

            return true;
        }

        return false;
    }

    protected function authenticated(Request $request, User $user): RedirectResponse
    {
        return redirect()->route('group', $user->primarygroup);
    }
}
