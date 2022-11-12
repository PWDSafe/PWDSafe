@extends('layouts.master')

@section('content')
    <div class="container mx-auto">
        <div class="w-full text-center my-8 mb-16">
            <i class="fas fa-lock text-6xl text-gray-600"></i>
        </div>
        <div class="card-container max-w-sm px-12 py-10 mx-auto shadow-md border bg-white">
            <form class="form-signin" method="post">
                @csrf
                <div class="form-group
            @if ($errors->any())
                    has-error
            @endif
                    " id="loginForm">
                    <label class="block text-sm font-medium leading-5 text-gray-700 mb-1" for="inputEmail">
                        Username
                    </label>
                    <div class="mb-1">
                        <input
                            type="text"
                            name="email"
                            id="inputEmail"
                            class="block w-full rounded-md px-4 py-2 placeholder-gray-400 border appearance-none focus:outline-none focus:shadow-outline-blue focus:border-blue-500 transition duration-150 ease-in-out @if($errors->any()) border-red-500 @endif" value="{{ old('email') }}"
                            required
                            :autofocus="'autofocus'"
                        >
                        @if ($errors->any())
                            <span class="text-red-600 text-xs">Wrong username or password</span>
                        @endif
                    </div>
                    <label class="block text-sm font-medium leading-5 text-gray-700 mb-1 mt-6" for="inputPassword">
                        Password
                    </label>
                    <div class="mb-4">
                        <input type="password" name="password" id="inputPassword" class="block w-full rounded-md px-4 py-2 placeholder-gray-400 border appearance-none focus:outline-none focus:shadow-outline-blue focus:border-blue-500 transition duration-150 ease-in-out @if($errors->any()) border-red-500 @endif" required>
                        @if ($errors->any())
                            <span class="text-red-600 text-xs">Wrong username or password</span>
                        @endif
                    </div>
                </div>
                <button class="btn-signin font-bold h-8 bg-gray-600 hover:bg-gray-700 w-full rounded text-white transition duration-150 ease-in-out" type="submit">Sign in</button>
                @if (!config('ldap.enabled'))
                    <a href="/register" class="block text-center py-1 font-bold h-8 bg-gray-600 hover:bg-gray-700 w-full rounded text-white mt-1 transition duration-150 ease-in-out">Register</a>
                @endif
            </form>
        </div>
    </div>
@endsection
