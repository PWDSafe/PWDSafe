<?php

namespace App\Http\Controllers;

use App\Credential;
use App\Encryptedcredential;
use App\Group;
use App\Http\Requests\StoreCredentialsRequest;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;

class GroupController extends Controller
{
    public function index(): Factory|View|Application
    {
        $groups = auth()
            ->user()
            ->groups
            ->filter(fn ($group) => $group->id !== auth()->user()->primarygroup);
        return view('groups.index', compact('groups'));
    }

    public function show(Group $group): Factory|View|Application
    {
        $this->authorize('view', $group);

        $credentials = Credential::with('group:id,name')
            ->where('groupid', $group->id)
            ->orderBy('site')
            ->get();

        return view('group', compact('group', 'credentials'));
    }

    public function create(): Factory|View|Application
    {
        return view('group.create');
    }

    public function addCredential(Group $group): Factory|View|Application
    {
        $this->authorize('update', $group);
        return view('credential.add', compact('group'));
    }

    public function storeCredential(StoreCredentialsRequest $request, Group $group): Response|Redirector|RedirectResponse|Application|ResponseFactory
    {
        $params = $request->validated();

        $credential = Credential::create([
            'groupid' => $group->id,
            'site' => $params['site'],
            'username' => $params['user'],
            'notes' => $params['notes'],
        ]);

        foreach ($params['encrypted'] as $entry) {
            Encryptedcredential::create([
                'credentialid' => $credential->id,
                'userid' => $entry['userid'],
                'data' => $entry['data'],
            ]);
        }

        if ($request->wantsJson()) {
            return response(['status' => 'OK']);
        }

        return redirect(route('group', $group->id));
    }

    public function pubkeys(Group $group): Response|Application|ResponseFactory
    {
        $this->authorize('view', $group);

        return response([
            'users' => $group->users()->get(['users.id', 'pubkey'])->map(fn ($u) => [
                'id' => $u->id,
                'pubkey' => $u->pubkey,
            ]),
        ]);
    }

    public function store(Request $request): Response|Redirector|RedirectResponse|Application|ResponseFactory
    {
        $params = $request->validate([
            'groupname' => 'required'
        ]);
        $group = Group::create(['name' => $params['groupname']]);
        auth()->user()->groups()->attach($group);

        if ($request->wantsJson()) {
            return response([
                'status' => "OK",
                "groupid" => $group->id
            ]);
        }

        return redirect()->route('group', $group->id);
    }
}
