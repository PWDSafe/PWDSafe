@extends('layouts.master')
@section('content')
    <div class="container">
        <div class="flex justify-between mb-5">
            <h3 class="text-2xl">
                Groups
            </h3>
            <div class="flex">
                <pwdsafe-button btntype="a" href="{{ route('groupCreate') }}" classes="mr-2 flex items-center">
                    <heroicons-plus-icon class="h-5 w-5 mr-1"></heroicons-plus-icon> Create
                </pwdsafe-button>
            </div>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @forelse ($groups as $group)
            <a
                href="{{ route('group', $group) }}"
                class="max-w-sm w-full card bg-white shadow flex justify-between p-4 rounded-md bg-white text-xl hover:border-indigo-500 focus:border-indigo-500 outline-none border duration-200 gap-x-2"
            >
                {{ $group->name }}
                <span class="flex items-start gap-x-2">
                    @if ($group->users_count > 1)
                        <span class="bg-gray-100 text-base text-gray-600 p-1 px-2 rounded-md flex items-center gap-x-0.5"><heroicons-user-icon class="w-4 h-4"></heroicons-user-icon>{{ $group->users_count }}</span>
                    @endif
                    <span class="bg-gray-100 text-base text-gray-600 p-1 px-2 rounded-md flex items-center gap-x-0.5"><heroicons-key-icon class="w-4 h-4"></heroicons-key-icon>{{ $group->credentials_count }}</span>
                </span>
            </a>
        @empty
            <pwdsafe-alert>
                <strong>No groups!</strong> You do not have any groups.
            </pwdsafe-alert>
        @endforelse
        </div>
    </div>
@endsection
