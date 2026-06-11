<?php

namespace App\Http\Controllers\Api;

use App\Group;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $groups = $user->groups()
            ->select('groups.id', 'groups.name', 'groups.parent_id')
            ->get()
            ->map(fn ($group) => [
                'id' => $group->id,
                'name' => $group->name,
                'parent_id' => $group->parent_id,
                'permission' => $group->pivot->permission,
                'is_primary' => $group->id === $user->primarygroup,
            ]);

        return response()->json($groups);
    }

    public function store(Request $request): JsonResponse
    {
        $params = $request->validate([
            'name' => 'required|string',
            'parent_id' => 'nullable|integer|exists:groups,id',
        ]);

        if ($params['parent_id'] ?? null) {
            $parentGroup = Group::findOrFail($params['parent_id']);
            $this->authorize('createSubGroup', $parentGroup);
        }

        $group = Group::create([
            'name' => $params['name'],
            'parent_id' => $params['parent_id'] ?? null,
        ]);

        $request->user()->groups()->attach($group, ['permission' => 'admin']);

        return response()->json([
            'id' => $group->id,
            'name' => $group->name,
            'parent_id' => $group->parent_id,
        ], 201);
    }
}
