<?php

namespace App\Http\Controllers;

use App\Credential;
use App\Encryptedcredential;
use App\Group;
use App\Helpers\Encryption;
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

    public function storeCredential(Request $request, Group $group): Response|Redirector|RedirectResponse|Application|ResponseFactory
    {
        $this->authorize('update', $group);

        $params = $request->validate([
            'site' => 'required',
            'user' => 'required',
            'pass' => 'required',
            'notes' => 'nullable'
        ]);

        $credential = new Credential();
        $credential->groupid = $group->id;
        $credential->site = $params['site'];
        $credential->username = $params['user'];
        $credential->notes = $params['notes'];
        $credential->save();

        $users = $group->users()->pluck('pubkey', 'users.id');

        foreach ($users as $userid => $pubkey) {
            $encrypted = new Encryptedcredential();
            $encrypted->credentialid = $credential->id;
            $encrypted->userid = $userid;
            $encrypted->data = app(Encryption::class)->encWithPub($params['pass'], $pubkey);
            $encrypted->save();
        }

        if ($request->wantsJson()) {
            return response(['status' => 'OK']);
        } else {
            return redirect(route('group', $group->id));
        }
    }

    public function store(Request $request): Response|Redirector|RedirectResponse|Application|ResponseFactory
    {
        $params = $request->validate([
            'groupname' => 'required'
        ]);
        $group = new Group();
        $group->name = $params['groupname'];
        $group->save();
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
