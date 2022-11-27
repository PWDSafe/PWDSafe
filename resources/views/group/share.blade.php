@extends('layouts.master')
@section('content')
    <div class="container">
        <h3 class="text-2xl mb-5">{{ $group->name }}</h3>
        @if ($group->userCountWithoutCurrentUser() > 0)
            <div class="flex flex-col">
                <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                        <div class="shadow overflow-hidden border-b border-gray-200 dark:border-gray-800 sm:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                                <thead>
                                <tr>
                                    <th class="px-6 py-3 bg-gray-50 dark:bg-gray-700 text-left text-xs leading-4 font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Username
                                    </th>
                                    <th class="px-6 py-3 bg-gray-50 dark:bg-gray-700 text-left text-xs leading-4 font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Permission
                                    </th>
                                    <th class="px-6 py-3 bg-gray-50 dark:bg-gray-700"></th>
                                </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-700 divide-y divide-gray-200 dark:divide-gray-800">
                                @foreach ($group->users as $user)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-no-wrap">
                                            {{ $user->email }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-no-wrap">
                                            @if (!auth()->user()->is($user))
                                                <update-permission
                                                    :userid="{{ $user->id }}"
                                                    :groupid="{{ $group->id }}"
                                                    permission="{{ $user->pivot->permission }}"
                                                ></update-permission>
                                            @else
                                                Admin
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-no-wrap text-right text-sm leading-5 font-medium">
                                            @if (!auth()->user()->is($user))
                                                <form method="post">
                                                    @csrf
                                                    @method('delete')
                                                    <input type="hidden" name="userid" value="{{ $user->id }}">
                                                    <pwdsafe-button theme="danger" type="submit">Remove</pwdsafe-button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <pwdsafe-alert theme="info">
                <strong>Not shared!</strong> This group isn't shared with anyone yet.
            </pwdsafe-alert>
        @endif
        <div class="mt-8 max-w-lg bg-white dark:bg-gray-700 rounded-md shadow overflow-hidden">
            <form method="post">
                <div class="px-8 py-4">
                    @csrf
                    <h4 class="text-xl mb-4">Share group</h4>
                    <div class="mb-4">
                        <label for="username"
                               class="block text-sm font-medium leading-5 text-gray-700 mb-1">Username</label>
                        <input type="text" id="username" name="username"
                               class="block w-full px-3 py-2 mb-2 border border-gray-300 dark:border-gray-700 rounded-md leading-5 bg-white dark:bg-gray-800 placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:border-indigo-500 focus:shadow-outline-blue sm:text-sm transition duration-150 ease-in-out"
                               placeholder="Username"
                               value="{{ old('email') }}"
                               required
                        >
                        <label for="permission" class="block text-sm font-medium leading-5 text-gray-700 mb-1">Permission</label>
                        <pwdsafe-select name="permission" id="permission">
                            <option value="read">Read</option>
                            <option value="write" selected>Read & write</option>
                            <option value="admin">Admin</option>
                        </pwdsafe-select>
                        @if ($errors->any())
                            <pwdsafe-alert theme="danger" classes="mt-4">
                                @foreach ($errors->all() as $error)
                                    {{ $error }}<br>
                                @endforeach
                            </pwdsafe-alert>
                        @endif
                    </div>
                </div>
                <div class="flex justify-end gap-x-2 bg-gray-50 dark:bg-gray-700 px-8 py-4 border-t dark:border-gray-800">
                    <pwdsafe-button theme="secondary" href="{{ route('group', $group) }}">
                        Back
                    </pwdsafe-button>
                    <pwdsafe-button type="submit">Share group</pwdsafe-button>
                </div>
            </form>
        </div>
    </div>
@endsection
