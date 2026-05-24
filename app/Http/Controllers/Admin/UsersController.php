<?php

namespace App\Http\Controllers\Admin;

use App\AuditLog;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    public function index(): Factory|View|Application
    {
        $users = User::select('users.*')
            ->selectSub(
                AuditLog::select('created_at')
                    ->whereColumn('user_id', 'users.id')
                    ->where('event', 'login')
                    ->orderBy('created_at', 'desc')
                    ->limit(1),
                'last_login_at'
            )
            ->orderBy('email')
            ->get();

        return view('admin.users.index', ['users' => $users]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'name' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::createPendingLocalUser(
            $validated['email'],
            $validated['password'],
            ($validated['name'] ?? null) ?: null,
        );

        return redirect()->route('admin.users')->with('success', "Account {$validated['email']} has been created. The user will set up their vault on first login.");
    }

    public function resetPassword(User $user, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->password = Hash::make($validated['password']);
        $user->uses_login_hash = false;
        $user->login_salt = null;
        if ($user->vault_configured) {
            $user->separate_vault_password = true;
        }
        $user->save();

        $message = $user->vault_configured
            ? "Password for {$user->email} has been reset. They will need their old safe password to unlock their vault on first login."
            : "Password for {$user->email} has been reset.";

        return redirect()->route('admin.users')->with('success', $message);
    }

    public function updateName(User $user, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $user->name = $validated['name'] ?: null;
        $user->save();

        return redirect()->route('admin.users')->with('success', "Name for {$user->email} has been updated.");
    }

    public function destroy(User $user, Request $request): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return redirect()->route('admin.users')->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users')->with('success', "Account {$user->email} has been deleted.");
    }
}
