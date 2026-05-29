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
<body class="bg-gray-100 dark:bg-gray-800 accent-indigo-500">
<div id="app" class="flex flex-col h-screen overflow-hidden">
    <nav class="flex-shrink-0 bg-white dark:bg-gray-700 dark:text-gray-300 shadow z-10">
        <div class="mx-auto px-2 sm:px-4 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex px-2 lg:px-0 items-center gap-x-3">
                    <!-- Sidebar toggle (all screen sizes) -->
                    <button
                        @click="sidebarOpen = !sidebarOpen"
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-gray-200 focus:outline-none transition duration-150 ease-in-out"
                        aria-label="Toggle sidebar"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <a href="/" class="text-gray-800 dark:text-gray-100 font-semibold text-sm">PWDSafe</a>
                </div>

                <div class="flex-1 flex items-center justify-end px-2 lg:ml-6 gap-x-3">
                    <!-- Search -->
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
                                       class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md leading-5 bg-white dark:bg-gray-800 placeholder:text-gray-500 dark:placeholder:text-gray-400 focus:outline-none focus:placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 sm:text-sm transition duration-150 ease-in-out"
                                       placeholder="Search" type="search">
                            </form>
                        </div>
                    </div>

                    <!-- Profile dropdown -->
                    <profile-menu
                        username="{{ auth()->user()->name ?? auth()->user()->email }}"
                        :ldap="{{ config('ldap.enabled') ? 'true' : 'false' }}"
                        :is-admin="{{ auth()->user()->is_admin ? 'true' : 'false' }}"
                        csrf='{{ csrf_token() }}'
                    ></profile-menu>
                </div>
            </div>
        </div>
    </nav>

    <!-- Body: sidebar + main -->
    <div class="flex flex-1 overflow-hidden">
        <!-- Sidebar overlay (mobile) -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-20 bg-gray-600 opacity-50 lg:hidden"
            @click="sidebarOpen = false"
        ></div>

        <!-- Sidebar -->
        <aside
            :class="sidebarOpen ? 'translate-x-0 lg:w-64' : '-translate-x-full lg:w-0'"
            class="fixed lg:relative inset-y-0 left-0 z-30 lg:z-auto w-64 flex-shrink-0 transition-all duration-200 ease-in-out bg-white dark:bg-gray-700 border-r border-gray-200 dark:border-gray-600 overflow-hidden"
        >
            <div class="w-64 h-full overflow-y-auto pt-16 lg:pt-0">
                <vault-sidebar :active-group-id="{{ $activeGroupId ?? 'null' }}"></vault-sidebar>
            </div>
        </aside>

        <!-- Main content -->
        <main class="flex-1 overflow-y-auto p-6 dark:text-gray-300">
            @if (session('success'))
                <div class="mb-4 text-sm text-green-700 dark:text-green-400">{{ session('success') }}</div>
            @endif
            @yield('content')
        </main>
    </div>

    <toast></toast>
    <vault-unlock-modal></vault-unlock-modal>
    @if (!auth()->user()->warning_seen)
        <warning-message :ldap="{{ config('ldap.enabled') ? 'true' : 'false' }}"></warning-message>
    @endif
</div>
<div id='modals' class='dark:text-gray-300'></div>
@stack('scripts')
</body>
</html>
