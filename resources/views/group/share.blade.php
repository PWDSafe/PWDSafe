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
                                        <td class="px-6 py-4">
                                            @if ($user->name)
                                                <div class="font-medium">{{ $user->name }}</div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</div>
                                            @else
                                                {{ $user->email }}
                                            @endif
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
                                                <a href="{{ route('memberDeleteConfirm', ['group' => $group, 'user' => $user]) }}" class="inline-block">
                                                    <pwdsafe-button theme="danger" type="button">Remove</pwdsafe-button>
                                                </a>
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
        <add-group-member
            :groupid="{{ $group->id }}"
            backlink="{{ route('group', $group) }}"
            :existing-member-ids="{{ json_encode($group->users->pluck('id')->values()) }}"
        ></add-group-member>
    </div>
@endsection
