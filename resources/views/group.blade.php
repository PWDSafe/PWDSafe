@extends('layouts.master')
@section('content')
<div class="container">
    <div class="clearfix">
        <div class="flex justify-between mb-5 gap-x-2">
            <h3 class="text-2xl flex items-center gap-x-2">
                @if ($group->id !== auth()->user()->primarygroup)
                    {{ $group->name }}
                @else
                    Private
                @endif
                <span class="bg-gray-100 dark:bg-gray-600 text-xs text-gray-500 dark:text-gray-400 px-2 py-0.5 rounded-full font-normal">
                    {{ $credentials->count() }}
                </span>
            </h3>
            <div class="flex items-end md:items-start gap-2 flex-col md:flex-row">
                @can('update', $group)
                <pwdsafe-button href="{{ route('addCredentials', $group->id) }}" classes="flex items-center">
                    <heroicons-plus-icon class="h-5 w-5 mr-1"></heroicons-plus-icon> Add
                </pwdsafe-button>
                @endcan
                @can('view', $group)
                <export-button
                    :groupid="{{ $group->id }}"
                    groupname="{{ $group->name }}"
                ></export-button>
                @endcan
                @can('update', $group)
                <import-button :groupid="{{ $group->id }}"></import-button>
                @endcan
                @can('administer', $group)
                    <group-management-menu groupid="{{ $group->id }}"></group-management-menu>
                @endcan
            </div>
        </div>
    </div>
    @if ($credentials->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach($credentials as $credential)
            <credential-card
                :credential="{{ $credential }}"
                :groups="{{ auth()->user()->groupsWithWriteAccess->map->only('id', 'name') }}"
                :can-update="{{ auth()->user()->can('update', $group) ? 'true' : 'false' }}"
            ></credential-card>
        @endforeach
    </div>
    @else
        <pwdsafe-alert>
            <strong>No credentials!</strong> You can add some if you'd like.
        </pwdsafe-alert>
    @endif
</div>
@endsection('content')
