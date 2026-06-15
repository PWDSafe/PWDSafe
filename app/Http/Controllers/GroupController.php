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
    public function index(): RedirectResponse
    {
        return redirect()->route('group', auth()->user()->primarygroup);
    }

    public function show(Group $group): Factory|View|Application
    {
        $this->authorize('view', $group);

        $credentials = Credential::with('group:id,name')
            ->where('groupid', $group->id)
            ->orderBy('name')
            ->get();

        $subGroups = $group->children()->withCount(['users', 'credentials', 'children'])->orderBy('name')->get();
        $ancestors = $group->ancestors();

        return view('group', compact('group', 'credentials', 'subGroups', 'ancestors'));
    }

    public function create(?Group $group = null): Factory|View|Application
    {
        if ($group !== null) {
            $this->authorize('createSubGroup', $group);
        }

        return view('group.create', ['parentGroup' => $group]);
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
            'name' => $params['name'],
            'url' => $params['url'] ?? null,
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

    public function store(Request $request, ?Group $group = null): Response|Redirector|RedirectResponse|Application|ResponseFactory
    {
        if ($group !== null) {
            $this->authorize('createSubGroup', $group);
        }

        $params = $request->validate([
            'groupname' => 'required',
        ]);

        $newGroup = Group::create([
            'name' => $params['groupname'],
            'parent_id' => $group?->id,
        ]);

        auth()->user()->groups()->attach($newGroup, ['permission' => 'admin']);

        if ($request->wantsJson()) {
            return response([
                'status' => 'OK',
                'groupid' => $newGroup->id,
            ]);
        }

        return redirect()->route('group', $newGroup->id);
    }
}
