<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
        $this->middleware(function ($request, $next) {
            abort_if(! config('app.registration_enabled', true), 403, 'Registration is disabled.');

            return $next($request);
        });
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param array<string, string> $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'email' => ['required', 'string', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'confirmed'],
            'encrypted_privkey' => ['required', 'string'],
            'privkey_salt' => ['required', 'string', 'size:64'],
            'pubkey' => ['required', 'string'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param array<string,string> $data
     * @return User
     */
    protected function create(array $data): User
    {
        User::registerFromClientData(
            $data['email'],
            $data['password'],
            $data['encrypted_privkey'],
            $data['privkey_salt'],
            $data['pubkey'],
        );

        return User::where('email', $data['email'])->first();
    }

    /**
     * The user was registered. Set vault_unlocked so the auth middleware passes
     * when the framework auto-logs-in the newly registered user.
     */
    protected function registered(Request $request, User $user): void
    {
        session()->put('vault_unlocked', true);
    }
}
