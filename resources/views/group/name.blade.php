@extends('layouts.master')
@section('content')
    <div class="container">
        <div class="bg-white dark:bg-gray-700 rounded-md shadow max-w-sm overflow-hidden">
            <form method="post">
                <div class="px-8 py-4">
                    @csrf
                    <label for="groupname" class="block text-sm font-medium leading-5 text-gray-700 dark:text-gray-300 mb-1">Group
                        name</label>
                    <input type="text" id="groupname" name="groupname"
                           class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md leading-5 bg-white dark:bg-gray-800 placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:border-indigo-500 focus:shadow-outline-blue sm:text-sm transition duration-150 ease-in-out"
                           placeholder="Group name" value="{{ $group->name }}">
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 dark:border-t dark:border-gray-800 px-8 py-4 flex justify-end gap-x-2">
                    <pwdsafe-button theme="secondary" href="{{ route('group', $group) }}">Back
                    </pwdsafe-button>
                    <pwdsafe-button type="submit">Change</pwdsafe-button>
                </div>
            </form>
        </div>
    </div>
@endsection
