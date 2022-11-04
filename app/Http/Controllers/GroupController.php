<?php

namespace App\Http\Controllers;

use App\Credential;
use App\Encryptedcredential;
use App\Group;
use App\Helpers\Encryption;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index(Request $request, Group $group)
    {
        $this->authorize('view', $group);

        $credentials = \App\Credential::with('group:id,name')->where('groupid', $group->id)->get();

        return view('group', compact('group', 'credentials'));
    }

    public function create()
    {
        return view('group.create');
    }

    public function addCredential(Group $group)
    {
        $this->authorize('update', $group);
        return view('credential.add', compact('group'));
    }

    public function storeCredential(Request $request, Group $group)
    {
        $this->authorize('update', $group);

        $params = $request->validate([
            'site' => 'required',
            'user' => 'required',
            'pass' => 'required',
            'notes' => 'nullable'
        ]);

        $credential = new Credential;
        $credential->groupid = $group->id;
        $credential->site = $params['site'];
        $credential->username = $params['user'];
        $credential->notes = $params['notes'];
        $credential->save();

        $users = $group->users()->get()->pluck('pubkey', 'id');

        foreach ($users as $userid => $pubkey) {
            $encrypted = new Encryptedcredential;
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

    public function store(Request $request)
    {
        $params = $this->validate($request, ['groupname' => 'required']);
        $group = new Group;
        $group->name = $params['groupname'];
        $group->save();
        auth()->user()->groups()->attach($group);

        if ($request->wantsJson()) {
            return response([
                'status' => "OK",
                "groupid" => $group->id
            ]);
        } else {
            return redirect(route('group', $group->id));
        }
    }
}
