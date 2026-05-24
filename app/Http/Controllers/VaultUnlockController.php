<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class VaultUnlockController extends Controller
{
    /**
     * Show the vault unlock page for users who have a separate vault password.
     * The client reads vault_data from sessionStorage (stored there by login.js)
     * to derive the vault key and decrypt the private key.
     */
    public function show(): View
    {
        return view('vault.unlock');
    }
}
