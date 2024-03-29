<?php

namespace App\Http\Controllers;

use App\Encryptedcredential;
use App\Group;
use App\Helpers\Encryption;
use App\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ManageGroupMembersController extends Controller
{
    public function index(Group $group): Factory|View|Application
    {
        $this->authorize('administer', $group);

        return view('group.share', compact('group'));
    }

    public function destroy(Request $request, Group $group): RedirectResponse
    {
        $this->authorize('administer', $group);
        $data = $request->validate([
            'userid' => ['required', 'exists:users,id']
        ]);

        Encryptedcredential::whereIn('credentialid', $group->credentials()->pluck('id'))->where('userid', $data['userid'])->delete();
        User::find($data['userid'])->groups()->detach($group);

        return redirect()->back();
    }

    public function update(Request $request, Group $group, User $user): Response|Application|ResponseFactory
    {
        $this->authorize('administer', $group);
        abort_if(auth()->user()->is($user), 403);
        $params = $request->validate([
            'permission' => [
                'required',
                Rule::in(['read', 'write', 'admin'])
            ]
        ]);
        $group->users()->updateExistingPivot($user, ['permission' => $params['permission']]);

        return response(['status' => 'OK']);
    }

    public function store(Request $request, Group $group): RedirectResponse
    {
        $this->authorize('administer', $group);
        $params = $request->validate([
            'username' => 'required',
            'permission' => ['required', Rule::in(['read', 'write', 'admin'])]
        ]);

        $user = User::where('email', $params['username'])->first();
        if (is_null($user)) {
            return redirect()->back()->withErrors('User does not exist')->withInput($request->all());
        }

        if ($user->groups->contains('id', $group->id)) {
            return redirect()->back();
        }

        $user->groups()->attach($group, ['permission' => $params['permission']]);

        $sql = "SELECT encryptedcredentials.data, encryptedcredentials.credentialid FROM encryptedcredentials
                        INNER JOIN credentials ON credentials.id = encryptedcredentials.credentialid
                        INNER JOIN groups ON credentials.groupid = groups.id
                        INNER JOIN usergroups ON usergroups.groupid = groups.id
                        WHERE usergroups.groupid = :groupid AND usergroups.userid = :userid
                        AND encryptedcredentials.userid = :userid2";
        $result = DB::select($sql, [
            'groupid' => $group->id,
            'userid' => auth()->user()->id,
            'userid2' => auth()->user()->id,
        ]);
        $encryption = app(Encryption::class);

        foreach ($result as $row) {
            $data = $encryption->decWithPriv(
                $row->data,
                $encryption->dec(auth()->user()->privkey, session()->get('password'))
            );
            $encryptedcred = new Encryptedcredential();
            $encryptedcred->credentialid = $row->credentialid;
            $encryptedcred->userid = $user->id;
            $encryptedcred->data = $encryption->encWithPub($data, $user->pubkey);
            $encryptedcred->save();
        }

        return redirect()->back();
    }
}
