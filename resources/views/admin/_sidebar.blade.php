<aside class="w-44 flex-shrink-0">
    <nav class="flex flex-col border-l border-gray-200 dark:border-gray-700">
        @foreach ([
            'admin.settings.auth'    => 'Authentication',
            'admin.settings.general' => 'General',
            'admin.users'            => 'Users',
        ] as $routeName => $label)
            <a href="{{ route($routeName) }}"
               class="block -ml-px pl-4 pr-3 py-2 text-sm border-l-2 {{ request()->routeIs($routeName)
                   ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400 font-medium'
                   : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600' }}">
                {{ $label }}
            </a>
        @endforeach
    </nav>
</aside>
