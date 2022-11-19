@extends('layouts.master')
@section('content')
    <div class="container mx-auto">
        <h3 class="text-2xl mb-4">Two factor authentication</h3>
        <p class="max-w-xl mb-4 text-gray-700 dark:text-gray-300">This is where you can enable two factor authentication for your account. This is <strong>highly</strong> recommended. Once this has been activated, there is no way to access your account without having access to your two factor app.</p>
        @if (session()->has('success'))
            <pwdsafe-alert theme="success" classes="mb-4 max-w-lg">
                {{ session()->get('success') }}
            </pwdsafe-alert>
        @endif

        @if (is_null(auth()->user()->two_factor_secret))
            <div class="bg-white dark:bg-gray-700 shadow-md rounded p-4 max-w-lg mb-6">
                <h2 class="mb-2 font-semibold">Step 1</h2>
                <p>Install any TOTP-based multifactor application</p>
            </div>

            <div class="bg-white dark:bg-gray-700 shadow-md rounded p-4 max-w-lg mb-6">
                <h2 class="mb-2 font-semibold">Step 2</h2>
                <p>Scan this code with your two factor authentication app:</p>
                {!! $qrcodeinline !!}
                <p>If you can't use the QR-code, you can either click this <a href="{{ $totpUrl }}">link</a> or enter this code manually:</p>
                <strong>{{ session()->get('2fa_secret') }}</strong>
            </div>

            <div class="bg-white dark:bg-gray-700 shadow-md rounded p-4 max-w-lg mb-6">
                <h2 class="mb-2 font-semibold">Step 3</h2>
                <p class="mb-4">Enter your current password and the one time password your application is generating below</p>
                <form method="post" action="{{ route('settings.twofa') }}" class="max-w-lg">
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
                            <pwdsafe-label class="mb-1" for="currentpassword">Current password</pwdsafe-label>
                            <pwdsafe-input type="password" name="currentpassword" id="currentpassword" autocomplete="off" placeholder="Current password" required autofocus></pwdsafe-input>
                        </div>
                        <div class="mb-2">
                            <pwdsafe-label class="mb-1" for="otpcode">One time code</pwdsafe-label>
                            <pwdsafe-input type="text" name="otpcode" id="otpcode" autocomplete="off" placeholder="xxxxxx" required></pwdsafe-input>
                        </div>
                    </div>
                    <div class="flex justify-between">
                        <pwdsafe-button type="submit">Activate 2FA</pwdsafe-button>
                    </div>
                </form>
            </div>
        @else
            <div class="bg-white shadow-md rounded p-4 max-w-lg mb-6">
                <h2 class="mb-2 font-semibold text-green-700">Two factor authentication is currently enabled</h2>
                <p class="text-gray-700">If you want to disable two factor authentication, enter your current password and a one time code below.</p>
                <form method="post" action="{{ route('settings.twofa') }}" class="max-w-lg">
                    @csrf
                    @method('delete')

                    @if ($errors->any())
                        <pwdsafe-alert theme="danger" classes="my-4">
                            @foreach($errors->all() as $error)
                                {{ $error }}
                            @endforeach
                        </pwdsafe-alert>
                    @endif

                    <div class="my-4">
                        <div class="mb-2">
                            <pwdsafe-label class="mb-1" for="currentpassword">Current password</pwdsafe-label>
                            <pwdsafe-input type="password" name="currentpassword" id="currentpassword" autocomplete="off" placeholder="Current password" required autofocus></pwdsafe-input>
                        </div>
                        <div class="mb-2">
                            <pwdsafe-label class="mb-1" for="otpcode">One time code</pwdsafe-label>
                            <pwdsafe-input type="text" name="otpcode" id="otpcode" autocomplete="off" placeholder="xxxxxx" required></pwdsafe-input>
                        </div>
                    </div>
                    <div class="flex justify-between">
                        <pwdsafe-button type="submit" theme="danger">Disable 2FA</pwdsafe-button>
                    </div>
                </form>
            </div>
        @endif
    </div>
@endsection()
