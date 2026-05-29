@php $activeGroupId = isset($parentGroup) ? $parentGroup->id : null; @endphp
@extends('layouts.vault')
@section('content')
<div>
    <form method="post" action="{{ isset($parentGroup) ? route('groupSubCreate', $parentGroup) : route('groupCreate') }}" class="max-w-sm">
        @csrf
        @if (isset($parentGroup))
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                Sub-group of: <a href="{{ route('group', $parentGroup) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ $parentGroup->id === auth()->user()->primarygroup ? 'Private' : $parentGroup->name }}</a>
            </p>
        @endif
        <div class="form-group">
            <label for="groupname" class="block text-sm font-medium leading-5 text-gray-700 dark:text-gray-300 mb-1">Group name</label>
            <input
                type="text"
                id="groupname"
                name="groupname"
                class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md leading-5 bg-white dark:bg-gray-700 placeholder:text-gray-500 focus:outline-none focus:placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 sm:text-sm transition duration-150 ease-in-out"
                placeholder="Group name"
                :autofocus="'autofocus'"
            >
        </div>
        <pwdsafe-button type="submit" classes="mt-4">{{ isset($parentGroup) ? 'Create sub-group' : 'Create group' }}</pwdsafe-button>
    </form>
</div>
@endsection
