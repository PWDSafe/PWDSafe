<?php

namespace App\Http\Controllers;

use App\Group;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;

class GroupChangeNameController extends Controller
{
    public function index(Group $group): Factory|View|Application
    {
        $this->authorize('administer', $group);

        return view('group.name', compact('group'));
    }

    public function store(Request $request, Group $group): Response|Redirector|RedirectResponse|Application|ResponseFactory
    {
        $this->authorize('administer', $group);
        $params = $this->validate($request, [
            'groupname' => 'required|max:100'
        ]);

        $groupname = preg_replace('/[^\p{L}\p{N}\-_ ]/u', "", trim($params['groupname']));

        abort_if(strlen($groupname) === 0, 400);

        $group->name = $groupname;
        $group->save();

        if ($request->wantsJson()) {
            return response(['status' => 'OK']);
        }

        return redirect()->route('group', $group->id);
    }
}
