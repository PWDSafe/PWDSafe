@extends('layouts.master')
@section('content')
    <div class="container mx-auto">
        <h3 class="text-2xl mb-4">Change password</h3>
        @if (session()->has('success'))
            <pwdsafe-alert theme="success" classes="mb-4">
                {{ session()->get('success') }}
            </pwdsafe-alert>
        @endif
        @if (config('ldap.enabled') && !auth()->user()->canDecryptPrivkey(session('password')))
            <div class="mb-4 text-gray-600">
                <h4 class="text-lg mb-2 text-amber-600">Warning</h4>
                <p class="mb-1">
                    You have logged in via LDAP/AD, but we cannot seem to decrypt your private key.<br>
                    The cause of this is most likely that you've changed your password in your LDAP/AD.
                </p>
                <p>
                    To get access to your stored passwords again, we need to re-encrypt your private key.<br>
                    Please fill out this form. If successful you will be redirected to your private group.
                </p>
            </div>
        @endif
        @if (!config('ldap.enabled') || !auth()->user()->canDecryptPrivkey(session('password')))
            <form method="post" action="{{ route('changepassword') }}" class="max-w-lg">
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
                <div class="flex justify-between">
                    <pwdsafe-button type="submit">Change password</pwdsafe-button>
                </div>
            </form>
        @else
            <div class="mb-4 text-gray-600">
                <h4 class="text-lg mb-2 text-amber-600">Warning</h4>
                <p class="mb-1">
                    This feature is disabled since you have logged in via LDAP/AD.
                </p>
            </div>
        @endif
    </div>
@endsection()
