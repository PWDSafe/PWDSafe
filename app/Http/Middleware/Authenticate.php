<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            return route('login');
        }

        return null;
    }

    public function handle($request, Closure $next, ...$guards)
    {
        if (auth()->check()) {
            $user = auth()->user();

            if (!$user->isVaultConfigured()) {
                if (!$request->expectsJson() && !$request->is('vault/setup') && !$request->is('api/vault/setup')) {
                    return redirect()->route('vault.setup');
                }
            } elseif ($user->hasSeparateVaultPassword() && !session()->has('vault_unlocked')) {
                if (!$request->expectsJson() && !$request->is('vault/unlock') && !$request->is('api/vault/confirm-unlock')) {
                    return redirect()->route('vault.unlock');
                }
            } elseif (!$user->hasSeparateVaultPassword() && !session()->has('vault_key') && !session()->has('password') && !session()->has('vault_unlocked')) {
                auth()->logout();
                return redirect('/');
            }
        }

        // @phpstan-ignore-next-line
        return parent::handle($request, $next, $guards);
    }
}
