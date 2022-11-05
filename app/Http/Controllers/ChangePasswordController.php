<?php
namespace App\Http\Controllers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ChangePasswordController extends Controller
{
    public function index(): Factory|View|Application
    {
        return view('changepassword');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_if(config('ldap.enabled'), Response::HTTP_FORBIDDEN);

        $validated = $request->validate([
            'oldpwd' => 'required',
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        if (session()->get('password') !== $validated['oldpwd']) {
            return redirect()->back()->withErrors(['oldpwd' => 'Old password missmatch']);
        }

        auth()->user()->changePassword($validated['password']);

        return redirect()->back()->with('success', 'Your password has been changed successfully');
    }
}
