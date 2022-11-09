<?php

namespace App\Http\Controllers;

use App\Helpers\Encryption;
use App\Helpers\LdapAuthentication;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\Response;

class ChangePasswordController extends Controller
{
    public function index(): Factory|View|Application
    {
        return view('changepassword');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_if(config('ldap.enabled') && auth()->user()->canDecryptPrivkey(session('password')), Response::HTTP_FORBIDDEN);

        $validated = $request->validate([
            'oldpwd' => 'required',
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        return config('ldap.enabled') ?
            $this->handleLdapPasswordChange($validated) :
            $this->handleLocalPasswordChange($validated);

    }

    /**
     * @param array<string,string> $validated
     * @return RedirectResponse
     * @throws \Exception
     */
    private function handleLdapPasswordChange(array $validated): RedirectResponse
    {
        $privatekey = app(Encryption::class)->dec(auth()->user()->privkey, $validated['oldpwd']);
        if (strlen($privatekey) === 0) {
            return redirect()->back()->withErrors(['oldpwd' => 'Old password missmatch']);
        }

        if (!LdapAuthentication::login(auth()->user()->email, $validated['password'])) {
            return redirect()->back()->withErrors(['newpwd' => 'New password is incorrect when authenticating to LDAP/AD']);
        }

        $user = auth()->user();
        $user->privkey = app(Encryption::class)->enc($privatekey, $validated['password']);
        $user->save();

        return redirect()->to('/groups/' . auth()->user()->primarygroup);
    }

    /**
     * @param array<string,string> $validated
     * @return RedirectResponse
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function handleLocalPasswordChange(array $validated): RedirectResponse
    {
        if (session()->get('password') !== $validated['oldpwd']) {
            return redirect()->back()->withErrors(['oldpwd' => 'Old password missmatch']);
        }

        auth()->user()->changePassword($validated['password']);

        return redirect()->back()->with('success', 'Your password has been changed successfully');
    }
}
