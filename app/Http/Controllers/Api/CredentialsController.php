<?php

namespace App\Http\Controllers\Api;

use App\Credential;
use App\Group;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CredentialsController extends Controller
{
    public function index(Group $group): JsonResponse
    {
        $this->authorize('view', $group);

        return response()->json(
            Credential::with('group:id,name')
                ->where('groupid', $group->id)
                ->orderBy('site')
                ->get()
        );
    }
}
