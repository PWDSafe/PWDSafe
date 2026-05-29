@php $activeGroupId = $group->id; @endphp
@extends('layouts.vault')
@section('content')
<div>
    {{-- Breadcrumbs (skip for private group root and when no ancestors) --}}
    @if ($group->id !== auth()->user()->primarygroup && $ancestors->isNotEmpty())
        <nav class="flex items-center gap-x-1 text-sm text-gray-500 dark:text-gray-400 mb-3">
            @php $isPrivateSubTree = $ancestors->first()->id === auth()->user()->primarygroup; @endphp
            @if (!$isPrivateSubTree)
                <a href="{{ route('groups') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition duration-150">Groups</a>
            @endif
            @foreach ($ancestors as $ancestor)
                @if (!$loop->first || !$isPrivateSubTree)
                    <heroicons-chevron-right-icon class="w-4 h-4 flex-shrink-0"></heroicons-chevron-right-icon>
                @endif
                <a href="{{ route('group', $ancestor) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition duration-150">{{ $ancestor->id === auth()->user()->primarygroup ? 'Private' : $ancestor->name }}</a>
            @endforeach
            <heroicons-chevron-right-icon class="w-4 h-4 flex-shrink-0"></heroicons-chevron-right-icon>
            <span class="text-gray-700 dark:text-gray-200">{{ $group->name }}</span>
        </nav>
    @endif

    <div class="flex justify-between mb-5 gap-x-2 items-start">
        <h3 class="text-2xl flex items-center gap-x-2 text-gray-900 dark:text-gray-100">
            @if ($group->id !== auth()->user()->primarygroup)
                {{ $group->name }}
            @else
                Private
            @endif
            <span class="bg-gray-100 dark:bg-gray-600 text-xs text-gray-500 dark:text-gray-400 px-2 py-0.5 rounded-full font-normal">
                {{ $credentials->count() }}
            </span>
        </h3>

        <div class="flex items-center gap-x-2">
            @can('update', $group)
            <pwdsafe-button href="{{ route('addCredentials', $group->id) }}" classes="flex items-center">
                <heroicons-plus-icon class="h-5 w-5 mr-1"></heroicons-plus-icon> Add
            </pwdsafe-button>
            @endcan
            <group-actions-menu
                groupid="{{ $group->id }}"
                groupname="{{ $group->id !== auth()->user()->primarygroup ? $group->name : 'Private' }}"
                :can-update="{{ auth()->user()->can('update', $group) ? 'true' : 'false' }}"
                :can-administer="{{ auth()->user()->can('administer', $group) ? 'true' : 'false' }}"
                :can-manage-members="{{ auth()->user()->can('manageMembers', $group) ? 'true' : 'false' }}"
                :can-create-sub-group="{{ auth()->user()->can('createSubGroup', $group) ? 'true' : 'false' }}"
                :can-delete="{{ auth()->user()->can('delete', $group) ? 'true' : 'false' }}"
            ></group-actions-menu>
        </div>
    </div>

    @if ($subGroups->isNotEmpty())
    <div class="mb-8">
        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Sub-groups</h4>
        <div class="flex flex-wrap gap-3">
            @foreach ($subGroups as $subGroup)
                <sub-group-card :group="{{ json_encode(['id' => $subGroup->id, 'name' => $subGroup->name, 'url' => route('group', $subGroup), 'users_count' => $subGroup->users_count, 'children_count' => $subGroup->children_count, 'credentials_count' => $subGroup->credentials_count]) }}"></sub-group-card>
            @endforeach
        </div>
    </div>
    @endif

    <credential-table
        :credentials="{{ $credentials }}"
        :group-id="{{ $group->id }}"
        :groups="{{ auth()->user()->groupsWithWriteAccess->map(fn ($g) => ['id' => $g->id, 'name' => $g->id === auth()->user()->primarygroup ? 'Private' : $g->name]) }}"
        :can-update="{{ auth()->user()->can('update', $group) ? 'true' : 'false' }}"
    ></credential-table>
</div>
@endsection('content')
