<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class SidebarController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth()->user();

        $groups = $user->groups()
            ->select('groups.id', 'groups.name', 'groups.parent_id')
            ->withCount('credentials')
            ->get();

        $byParent = $groups->groupBy('parent_id');

        $buildNode = function ($group) use (&$buildNode, $byParent): array {
            $children = $byParent->get($group->id, collect())
                ->sortBy('name')
                ->map(fn ($child) => $buildNode($child))
                ->values()
                ->all();

            return [
                'id' => $group->id,
                'name' => $group->name,
                'url' => route('group', $group->id),
                'credentialsCount' => $group->credentials_count,
                'children' => $children,
            ];
        };

        $privateGroup = $groups->firstWhere('id', $user->primarygroup);

        $shared = $byParent->get(null, collect())
            ->filter(fn ($g) => $g->id !== $user->primarygroup)
            ->sortBy('name')
            ->map(fn ($g) => $buildNode($g))
            ->values()
            ->all();

        return response()->json([
            'private' => $privateGroup ? $buildNode($privateGroup) : null,
            'shared' => $shared,
        ]);
    }
}
