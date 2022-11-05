<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ChangePasswordController extends Controller
{
    public function index()
    {
        return view('changepassword');
    }

    public function store(Request $request)
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
