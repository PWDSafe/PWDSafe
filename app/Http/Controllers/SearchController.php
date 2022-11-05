<?php

namespace App\Http\Controllers;

use App\Credential;
use App\Policies\CredentialPolicy;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;

class SearchController extends Eloquent
{
    public function index(string $search): Factory|View|Application
    {
        $groups = auth()->user()->groups->pluck('id');
        $credentials = Credential::with('group:id,name')
            ->whereIn('groupid', $groups)
            ->where(function ($query) use ($search) {
                $query->where('site', 'like', '%' . $search . '%')
                    ->orWhere('username', 'like', '%' . $search . '%');
            })
            ->get();

        return view('search', ['data' => $credentials]);
    }

    public function store(Request $request): Redirector|Application|RedirectResponse
    {
        return redirect('/search/' . $request->input('search'));
    }
}
