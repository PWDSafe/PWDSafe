@extends('layouts.master')
@section('content')
    <div class="container">
        <h3 class="text-2xl mb-5">{{ $group->name }}</h3>
        @if ($group->usersWithoutCurrentUser->count() > 0)
            <div class="flex flex-col">
                <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                        <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                <tr>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                                        Username
                                    </th>
                                    <th class="px-6 py-3 bg-gray-50"></th>
                                </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($group->usersWithoutCurrentUser as $user)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-no-wrap">
                                            {{ $user->email }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-no-wrap text-right text-sm leading-5 font-medium">
                                            <form method="post">
                                                @csrf
                                                @method('delete')
                                                <input type="hidden" name="userid" value="{{ $user->id }}">
                                                <pwdsafe-button theme="danger" type="submit"><i class="far fa-trash-alt"></i> Remove</pwdsafe-button>
                                            </form>
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
        <div class="mt-8 max-w-sm">
            <form method="post">
                @csrf
                <h4 class="text-xl mb-2">Share group</h4>
                <div class="mb-4">
                    <label for="username" class="block text-sm font-medium leading-5 text-gray-700 mb-1">Username</label>
                    <input type="text" id="username" name="username"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:border-indigo-500 focus:shadow-outline-blue sm:text-sm transition duration-150 ease-in-out"
                           placeholder="Username"
                           value="{{ old('email') }}"
                           required
                           :autofocus="'autofocus'"
                    >
                    @if ($errors->any())
                        <pwdsafe-alert theme="danger" classes="mt-4">
                            @foreach ($errors->all() as $error)
                                {{ $error }}<br>
                            @endforeach
                        </pwdsafe-alert>
                    @endif
                </div>
                <pwdsafe-button type="submit">Share group</pwdsafe-button>
            </form>
        </div>
    </div>
@endsection
