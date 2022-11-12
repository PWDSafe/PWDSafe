@extends('layouts.master')

@section('content')
    <div class="container mx-auto">
        <div class="w-full text-center my-8 mb-16">
            <i class="fas fa-lock text-6xl text-gray-600"></i>
        </div>
        <div class="card-container max-w-sm px-12 py-10 mx-auto shadow-md border bg-white">
            <form method="post">
                @csrf
                <div @class(["form-group", "has-error" => $errors->any()])>
                    <label class="block text-sm font-medium leading-5 text-gray-700 mb-1" for="twofacode">
                        Verify two factor authentication
                    </label>
                    <div class="mb-1">
                        <input
                            type="text"
                            name="twofacode"
                            id="twofacode"
                            class="block w-full rounded-md px-4 py-2 placeholder-gray-400 border appearance-none focus:outline-none focus:shadow-outline-blue focus:border-blue-500 transition duration-150 ease-in-out @if($errors->any()) border-red-500 @endif"
                            value=""
                            placeholder="xxxxxx"
                            required
                            :autofocus="'autofocus'"
                        >
                        @if ($errors->any())
                            <span class="text-red-600 text-xs">Wrong two factor authentication code</span>
                        @endif
                    </div>
                </div>
                <button class="font-bold h-8 bg-gray-600 hover:bg-gray-700 w-full rounded text-white transition duration-150 ease-in-out" type="submit">
                    Verify login
                </button>
            </form>
        </div>
    </div>
@endsection
