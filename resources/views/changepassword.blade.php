@extends('layouts.master')
@section('content')
    <div class="container mx-auto">
        <h3 class="text-2xl mb-4">Change password</h3>
        @if (session()->has('success'))
            <pwdsafe-alert theme="success" classes="mb-4 max-w-lg">
                {{ session()->get('success') }}
            </pwdsafe-alert>
        @endif
        @if (config('ldap.enabled') && !auth()->user()->canDecryptPrivkey(session('password')))
            <div class="mt-8 max-w-3xl bg-white dark:bg-gray-700 rounded-md shadow px-8 py-4 mb-4 text-gray-600 dark:text-gray-300">
                <h4 class="text-xl mb-2 text-amber-600">Warning</h4>
                <p class="mb-2">
                    You have logged in via LDAP/AD, but we cannot seem to decrypt your private key.<br>
                    The cause of this is most likely that you've changed your password in your LDAP/AD.
                </p>
                <p class='mb-2'>
                    To get access to your stored passwords again, we need to re-encrypt your private key.<br>
                    Please fill out this form. If successful you will be redirected to your private group.
                </p>
                <p class='text-red-500'>
                    If you do not remember your old password, you can <a href='/settings/resetaccount' class='underline'>reset your account</a>. This will cause all secrets you have stored to be deleted.
                </p>
            </div>
        @endif
        @if (!config('ldap.enabled') || !auth()->user()->canDecryptPrivkey(session('password')))
            <form method="post" action="{{ route('changepassword') }}" class="max-w-lg">
                <div class="mt-8 max-w-lg bg-white dark:bg-gray-700 rounded-md shadow">
                    <div class="px-8 py-4">
                @if ($errors->any())
                    <pwdsafe-alert theme="danger" classes="my-4">
                    @foreach($errors->all() as $error)
                        {{ $error }}
                    @endforeach
                    </pwdsafe-alert>
                @endif
                @csrf
                <div class="mb-4">
                    <div class="mb-2">
                        <pwdsafe-label class="mb-1" for="oldpwd">Old password</pwdsafe-label>
                        <pwdsafe-input type="password" name="oldpwd" id="oldpwd" autocomplete="off" required autofocus></pwdsafe-input>
                    </div>
                    <div class="mb-2">
                        <pwdsafe-label class="mb-1" for="password">New password</pwdsafe-label>
                        <pwdsafe-input type="password" name="password" id="password" autocomplete="off" required></pwdsafe-input>
                    </div>
                    <div class="mb-2">
                        <pwdsafe-label class="mb-1" for="password_confirmation">Verify</pwdsafe-label>
                        <pwdsafe-input type="password" name="password_confirmation" id="password_confirmation" autocomplete="off" required></pwdsafe-input>
                    </div>
                </div>
                    </div>
                <div class="flex justify-end gap-x-2 bg-gray-50 dark:bg-gray-700 dark:border-t dark:border-gray-800 px-8 py-4">
                    <pwdsafe-button type="submit">Change password</pwdsafe-button>
                </div>
                </div>
            </form>
        @else
            <div class="mt-8 max-w-xl bg-white rounded-md shadow px-8 py-4 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                <h4 class="text-xl mb-2 text-amber-600">Warning</h4>
                <p>
                    This feature is disabled since you have logged in via LDAP/AD.
                </p>
            </div>
        @endif
    </div>
@endsection()
