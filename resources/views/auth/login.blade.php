@extends('layouts.master')

@section('content')
    <div class="container mx-auto">
        <div class="flex justify-center mt-12 mb-16">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="text-gray-600 w-20 h-20">
                <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z" clip-rule="evenodd" />
            </svg>
        </div>

        @php $internalFallback = request()->boolean('internal'); @endphp

        @if (config('app.auth_method') === 'oidc' && !$internalFallback)
            <div class="card-container max-w-sm px-12 py-10 mx-auto shadow-md border bg-white dark:bg-gray-700 dark:border-gray-700">
                @if ($errors->any())
                    <div class="mb-4 text-sm text-red-600 dark:text-red-400">
                        @foreach($errors->all() as $error)
                            {{ $error }}
                        @endforeach
                    </div>
                @endif
                <a
                    href="{{ route('oidc.redirect') }}"
                    class="flex items-center justify-center gap-2 font-bold h-10 bg-gray-600 hover:bg-gray-700 dark:hover:bg-gray-800 w-full rounded text-white transition duration-150 ease-in-out"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-5.5-2.5a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0zM10 12a5.99 5.99 0 00-4.793 2.39A6.483 6.483 0 0010 16.5a6.483 6.483 0 004.793-2.11A5.99 5.99 0 0010 12z" clip-rule="evenodd" />
                    </svg>
                    Login with SSO
                </a>
                <p class="mt-6 text-center text-xs text-gray-400 dark:text-gray-500">
                    Admin? <a href="{{ route('login') }}?internal=1" class="hover:underline">Sign in with password</a>
                </p>
            </div>
        @else
            <div class="card-container max-w-sm px-12 py-10 mx-auto shadow-md border bg-white dark:bg-gray-700 dark:border-gray-700">
                @if ($internalFallback)
                    <p class="mb-4 text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 rounded px-3 py-2">
                        Internal account login. Users provisioned via LDAP or SSO will not have a local password unless one has been set manually.
                    </p>
                @endif
                <form class="form-signin" method="post">
                    @csrf
                    @if ($internalFallback)
                        <input type="hidden" name="internal" value="1">
                    @endif
                    <div class="form-group
                @if ($errors->any())
                        has-error
                @endif
                        " id="loginForm">
                        <label class="block text-sm font-medium leading-5 text-gray-700 dark:text-gray-300 mb-1" for="inputEmail">
                            Username
                        </label>
                        <div class="mb-1">
                            <pwdsafe-input
                                type="text"
                                name="email"
                                id="inputEmail"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                :error="{{ $errors->any() ? 'true' : 'false' }}"
                            ></pwdsafe-input>
                            @if ($errors->any())
                                <span class="text-red-600 dark:text-red-400 text-xs">Wrong username or password</span>
                            @endif
                            <span id="js-login-error" class="text-red-600 dark:text-red-400 text-xs hidden">Wrong username or password</span>
                        </div>
                        <label class="block text-sm font-medium leading-5 text-gray-700 dark:text-gray-300 mb-1 mt-6" for="inputPassword">
                            Password
                        </label>
                        <div class="mb-4">
                            <pwdsafe-input
                                type="password"
                                name="password"
                                id="inputPassword"
                                required
                                :error="{{ $errors->any() ? 'true' : 'false' }}"
                            ></pwdsafe-input>
                            @if ($errors->any())
                                <span class="text-red-600 dark:text-red-400 text-xs">Wrong username or password</span>
                            @endif
                        </div>
                    </div>
                    <button class="btn-signin font-bold h-8 bg-gray-600 hover:bg-gray-700 dark:hover:bg-gray-800 disabled:opacity-60 disabled:cursor-not-allowed w-full rounded text-white transition duration-150 ease-in-out flex items-center justify-center gap-2" type="submit" id="login-submit">
                        <svg id="login-spinner" class="hidden animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span id="login-submit-text">Sign in</span>
                    </button>
                    @if (!config('ldap.enabled') && config('app.registration_enabled') && !$internalFallback)
                        <a href="/register" class="block text-center py-1 font-bold h-8 bg-gray-600 hover:bg-gray-700 dark:hover:bg-gray-800 w-full rounded text-white mt-1 transition duration-150 ease-in-out">Register</a>
                    @endif
                </form>
            </div>
        @endif
    </div>
@push('scripts')
    @vite('resources/js/login.js')
@endpush
@endsection
