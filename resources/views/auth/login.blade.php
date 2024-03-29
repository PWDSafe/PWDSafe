@extends('layouts.master')

@section('content')
    <div class="container mx-auto">
        <div class="flex justify-center mt-12 mb-16">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="text-gray-600 w-20 h-20">
                <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z" clip-rule="evenodd" />
            </svg>
        </div>
        <div class="card-container max-w-sm px-12 py-10 mx-auto shadow-md border bg-white dark:bg-gray-700 dark:border-gray-700">
            <form class="form-signin" method="post">
                @csrf
                <div class="form-group
            @if ($errors->any())
                    has-error
            @endif
                    " id="loginForm">
                    <label class="block text-sm font-medium leading-5 text-gray-700 dark:text-gray-300 mb-1" for="inputEmail">
                        Username
                    </label>
                    <div class="mb-1">
                        <input
                            type="text"
                            name="email"
                            id="inputEmail"
                            class="block w-full rounded-md px-4 py-2 placeholder-gray-400 dark:bg-gray-800 dark:border-gray-700 border appearance-none focus:outline-none focus:shadow-outline-blue focus:border-blue-500 transition duration-150 ease-in-out @if($errors->any()) border-red-500 @endif" value="{{ old('email') }}"
                            required
                            :autofocus="'autofocus'"
                        >
                        @if ($errors->any())
                            <span class="text-red-600 dark:text-red-400 text-xs">Wrong username or password</span>
                        @endif
                    </div>
                    <label class="block text-sm font-medium leading-5 text-gray-700 dark:text-gray-300 mb-1 mt-6" for="inputPassword">
                        Password
                    </label>
                    <div class="mb-4">
                        <input type="password" name="password" id="inputPassword" class="block w-full rounded-md px-4 py-2 placeholder-gray-400 dark:bg-gray-800 dark:border-gray-700 border appearance-none focus:outline-none focus:shadow-outline-blue focus:border-blue-500 transition duration-150 ease-in-out @if($errors->any()) border-red-500 @endif" required>
                        @if ($errors->any())
                            <span class="text-red-600 dark:text-red-400 text-xs">Wrong username or password</span>
                        @endif
                    </div>
                </div>
                <button class="btn-signin font-bold h-8 bg-gray-600 hover:bg-gray-700 dark:hover:bg-gray-800 w-full rounded text-white transition duration-150 ease-in-out" type="submit">Sign in</button>
                @if (!config('ldap.enabled'))
                    <a href="/register" class="block text-center py-1 font-bold h-8 bg-gray-600 hover:bg-gray-700 dark:hover:bg-gray-800 w-full rounded text-white mt-1 transition duration-150 ease-in-out">Register</a>
                @endif
            </form>
        </div>
    </div>
@endsection
