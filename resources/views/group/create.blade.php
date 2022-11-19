@extends('layouts.master')
@section('content')
<div class="container">
    <form method="post" action="{{ route('groupCreate') }}" class="max-w-sm">
        @csrf
        <div class="form-group">
            <label for="groupname" class="block text-sm font-medium leading-5 text-gray-700 dark:text-gray-300 mb-1">Group name</label>
            <input
                type="text"
                id="groupname"
                name="groupname"
                class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md leading-5 bg-white dark:bg-gray-700 placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:border-indigo-500 focus:shadow-outline-blue sm:text-sm transition duration-150 ease-in-out"
                placeholder="Group name"
                :autofocus="'autofocus'"
            >
        </div>
        <pwdsafe-button type="submit" classes="mt-4">Create group</pwdsafe-button>
    </form>
</div>
@endsection
