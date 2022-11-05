<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class PreLogonFirstPageCallback extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->to('/groups/' . auth()->user()->primarygroup);
    }
}
