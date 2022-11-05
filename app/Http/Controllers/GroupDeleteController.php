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

class GroupDeleteController extends Controller
{
    public function index(Group $group): Factory|View|Application
    {
        $this->authorize('delete', $group);

        return view('group.delete', compact('group'));
    }

    public function delete(Request $request, Group $group): Response|Redirector|RedirectResponse|Application|ResponseFactory
    {
        $this->authorize('delete', $group);
        $group->deleteGroup();

        return $request->wantsJson() ?
            response(['status' => 'OK']) :
            redirect('/');
    }
}
