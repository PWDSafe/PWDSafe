<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    <title>PWDSafe</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 dark:bg-gray-800">
<div id="app">
    @auth
        <nav class="bg-white dark:bg-gray-700 dark:text-gray-300 dark:hover:text-gray-200 shadow">
            <div class="mx-auto px-2 sm:px-4 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex px-2 lg:px-0">
                        <div class="flex-shrink-0 flex items-center">
                            <a href="/">PWDSafe</a>
                        </div>
                        <div class="hidden lg:ml-6 lg:flex">
                            <a href="{{ route('group', auth()->user()->primarygroup) }}"
                               class="inline-flex items-center px-1 pt-1 border-b-2 {{ (request()->is('groups/' . auth()->user()->primarygroup)) ? 'border-indigo-500 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 dark:text-gray-300 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300' }} text-sm font-medium leading-5 focus:outline-none focus:border-indigo-700 transition duration-150 ease-in-out ml-8">
                                Private
                            </a>
                            <a href="{{ route('groups') }}"
                               class="inline-flex items-center px-1 pt-1 border-b-2 {{ (request()->is('groups')) ? 'border-indigo-500 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 dark:text-gray-300 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300' }} text-sm font-medium leading-5 focus:outline-none focus:border-indigo-700 transition duration-150 ease-in-out ml-8">
                                Groups
                            </a>
                            <a href="{{ route('securitycheck') }}"
                               class="inline-flex items-center px-1 pt-1 border-b-2 {{ (request()->is('securitycheck')) ? 'border-indigo-500 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 dark:text-gray-300 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300' }} text-sm font-medium leading-5 focus:outline-none focus:border-indigo-700 transition duration-150 ease-in-out ml-8">
                                Security check
                            </a>
                        </div>
                    </div>
                    <div class="flex-1 flex items-center justify-center px-2 lg:ml-6 lg:justify-end">
                        <div class="max-w-lg w-full lg:max-w-xs">
                            <label for="search" class="sr-only">Search</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                              d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                              clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <form method="post" action="{{ route('search') }}">
                                    @csrf
                                    <input id="search" name="search"
                                           class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md leading-5 bg-white dark:bg-gray-800 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:placeholder-gray-400 focus:border-indigo-500 focus:shadow-outline-blue sm:text-sm transition duration-150 ease-in-out"
                                           placeholder="Search" type="search">
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center lg:hidden">
                        <!-- Mobile menu button -->
                        <button
                            @click="mobileMenuOpen = !mobileMenuOpen"
                            class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out"
                            aria-label="Main menu" aria-expanded="false">
                            <svg :class="{'block': !mobileMenuOpen, 'hidden': mobileMenuOpen}" class="h-6 w-6"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                            <svg :class="{'block': mobileMenuOpen, 'hidden': !mobileMenuOpen}" class="h-6 w-6"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="hidden lg:ml-4 lg:flex lg:items-center">
                        <!-- Profile dropdown -->
                        <dropdown-menu>
                            <template v-slot:trigger="{ open }">
                                <span class="flex items-center hover:text-gray-700 focus:text-gray-700"
                                      :class="{'text-gray-500 dark:text-gray-300 dark:hover:text-gray-200': !open, 'text-gray-700 dark:text-gray-200': open}">
                                    <heroicons-user-icon class="h-5 w-5 mr-1"></heroicons-user-icon>
                                    {{ auth()->user()->email }}
                                </span>
                            </template>
                            <template v-slot:default>
                                @if (!config('ldap.enabled'))
                                    <dropdown-link href="{{ route('changepassword') }}">Change password</dropdown-link>
                                @endif
                                <dropdown-link href="{{ route('settings.twofa') }}">Two factor authentication</dropdown-link>
                                <div class="my-1 border-b dark:border-gray-800"></div>
                                <form method="post" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                            class="w-full dark:focus:bg-gray-700 text-left block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 dark:bg-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200 dark:text-gray-300 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">
                                        Logout
                                    </button>
                                </form>
                            </template>
                        </dropdown-menu>
                    </div>
                </div>
            </div>

            <div class="lg:hidden" :class="{'block': mobileMenuOpen, 'hidden': !mobileMenuOpen}">
                <div class="pt-2 pb-3">
                    @foreach (auth()->user()->groups()->withCount('credentials')->get() as $group)
                        <a href="{{ route('group', $group->id) }}"
                           class="mt-1 block pl-3 pr-4 py-2 border-l-4 items-center px-1 pt-1 text-base font-medium leading-5 focus:outline-none focus:border-indigo-700 transition duration-150 ease-in-out hover:bg-gray-100 {{ request()->is('groups/' . $group->id) ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-600 hover:text-gray-800 hover:border-gray-300' }}">
                            <span class="flex items-center justify-between">
                                @if ($group->id === auth()->user()->primarygroup)
                                    Private
                                @else
                                    {{ $group->name }}
                                @endif
                                    <span
                                        class="bg-gray-200 text-indigo-500 p-1 px-2 ml-2 rounded-md">{{ $group->credentials_count }}</span>
                                </span>
                        </a>
                    @endforeach
                </div>
                <div class="pt-4 pb-3 border-t border-gray-200">
                    <div class="mt-3">
                        <a href="{{ route('groupCreate') }}"
                           class="mt-1 block pl-3 pr-4 py-2 border-l-4 items-center px-1 pt-1 {{ (request()->is('groups/create')) ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-600 hover:text-gray-800 hover:border-gray-300' }} text-base font-medium leading-5 focus:outline-none focus:border-indigo-700 transition duration-150 ease-in-out hover:bg-gray-100">
                            Create group
                        </a>
                        <a href="{{ route('securitycheck') }}"
                           class="mt-1 block pl-3 pr-4 py-2 border-l-4 items-center px-1 pt-1 {{ (request()->is('securitycheck')) ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-600 hover:text-gray-800 hover:border-gray-300' }} text-base font-medium leading-5 focus:outline-none focus:border-indigo-700 transition duration-150 ease-in-out hover:bg-gray-100">
                            Security check
                        </a>
                        <form method="post" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <button type="submit"
                                    class="w-full text-left mt-1 block pl-3 pr-4 py-2 border-l-4 items-center px-1 pt-1 border-transparent text-gray-600 hover:text-gray-800 hover:border-gray-300 text-base font-medium leading-5 focus:outline-none focus:border-indigo-700 transition duration-150 ease-in-out hover:bg-gray-100">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>
    @endauth
    <div class="container mx-auto mt-4 px-4 sm:px-8 dark:text-gray-300">
        @yield('content')
    </div>
        @auth
            @if (!auth()->user()->warning_seen)
                <warning-message :ldap="{{ config('ldap.enabled') ? 'true' : 'false' }}"></warning-message>
            @endif
        @endauth
</div>
</body>
</html>
