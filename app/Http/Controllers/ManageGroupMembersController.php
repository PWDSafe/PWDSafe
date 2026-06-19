<?php

namespace App\Http\Controllers;

use App\Encryptedcredential;
use App\Group;
use App\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ManageGroupMembersController extends Controller
{
    public function confirmRemove(Group $group, User $user): RedirectResponse|View
    {
        $this->authorize('administer', $group);
        if (Auth::user()->is($user)) {
            abort(403, 'You cannot remove yourself from this group.');
        }

        return view('group.member-delete', compact('group', 'user'));
    }

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

        return redirect()->route('groupManageMembers', ['group' => $group]);
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
        // This endpoint is kept for legacy compatibility but the share form now uses
        // the two-step API (prepare + confirm) to handle re-encryption client-side.
        return redirect()->back()->withErrors('Please use the share form on this page.');
    }
}
