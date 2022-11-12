@extends('layouts.master')

@section('content')
    <div class="container mx-auto">
        <div class="w-full text-center my-8 mb-16">
            <i class="fas fa-lock text-6xl text-gray-600"></i>
        </div>
        <div class="card-container max-w-sm px-12 py-10 mx-auto shadow-md border bg-white">
            <form method="post">
                @if ($errors->any())
                    <pwdsafe-alert theme="danger" classes="mt-4">
                        @foreach ($errors->all() as $error)
                            {{ $error }}<br>
                        @endforeach
                    </pwdsafe-alert>
                @endif
                @csrf
                <div class="form-group">
                    <label class="block text-sm font-medium leading-5 text-gray-700 mb-1" for="inputEmail">
                        Username
                    </label>
                    <div class="mb-1">
                        <input type="text"
                               name="email"
                               id="inputEmail"
                               class="block w-full rounded-md px-4 py-2 placeholder-gray-400 border appearance-none focus:outline-none focus:shadow-outline-blue focus:border-blue-500 transition duration-150 ease-in-out @if($errors->any()) border-red-500 @endif"
                               value="{{ old('email') }}"
                               required
                               :autofocus="'autofocus'"
                        >
                    </div>
                    <label class="block text-sm font-medium leading-5 text-gray-700 mb-1 mt-6" for="inputPassword">
                        Password
                    </label>
                    <div class="mb-1">
                        <input type="password" name="password" id="inputPassword"
                               class="block w-full rounded-md px-4 py-2 placeholder-gray-400 border appearance-none focus:outline-none focus:shadow-outline-blue focus:border-blue-500 transition duration-150 ease-in-out @if($errors->any()) border-red-500 @endif"
                               required>
                    </div>
                    <label class="block text-sm font-medium leading-5 text-gray-700 mb-1 mt-6" for="inputPassword">
                        Verify password
                    </label>
                    <div class="mb-4">
                        <input type="password" name="password_confirmation"
                               class="block w-full rounded-md px-4 py-2 placeholder-gray-400 border appearance-none focus:outline-none focus:shadow-outline-blue focus:border-blue-500 transition duration-150 ease-in-out @if($errors->any()) border-red-500 @endif"
                               required>
                    </div>
                </div>
                <button type="submit"
                   class="block text-center py-1 font-bold h-8 bg-gray-600 hover:bg-gray-700 w-full rounded text-white mt-1 transition duration-150 ease-in-out">Register</button>
                <a href="/"
                   class="block text-center py-1 font-bold h-8 border border-gray-600 text-gray-600 hover:bg-gray-600 w-full rounded hover:text-white mt-1 transition duration-150 ease-in-out">Back</a>
            </form>
        </div>
    </div>
@endsection

