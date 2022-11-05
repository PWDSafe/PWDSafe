<?php

namespace App\Http\Controllers;

use App\Credential;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;

class CredentialsController extends Controller
{
    public function index(Credential $credential): Factory|View|Application
    {
        $this->authorize('delete', $credential);

        return view('credential.index', compact('credential'));
    }

    public function update(Request $request, Credential $credential): Response|Redirector|RedirectResponse|Application|ResponseFactory
    {
        $this->authorize('update', $credential);
        $params = $request->validate([
            'creds' => 'required',
            'credu' => 'required',
            'credp' => 'required',
            'currentgroupid' => 'required',
            'credn' => 'nullable',
        ]);

        if ($credential->groupid != $params['currentgroupid']) {
            Credential::addCredentials($params);
            $credential->delete();
        } else {
            Credential::updateCredentials($credential, $params);
        }

        if ($request->wantsJson()) {
            return response(['status' => 'OK']);
        }

        return redirect(route('group', $params['currentgroupid']));
    }

    public function delete(Credential $credential): Redirector|Application|RedirectResponse
    {
        $this->authorize('delete', $credential);
        $group = $credential->groupid;
        $credential->deleteCredential();

        return redirect(route('group', $group));
    }
}
