@extends('layouts.master')
@section('content')
@php
    $isExternalAuth = in_array(auth()->user()->auth_source, ['ldap', 'oidc']);
@endphp
    <div class="container mx-auto">
        <h3 class="text-2xl mb-6">Settings</h3>

        <div class="flex gap-10 items-start">
            {{-- Sidebar nav --}}
            <aside class="w-44 flex-shrink-0">
                <nav class="flex flex-col border-l border-gray-200 dark:border-gray-700">
                    @foreach ([
                        'profile' => ['label' => 'Profile',        'route' => 'settings'],
                        'login'   => ['label' => 'Login password', 'route' => 'settings.login'],
                        'vault'   => ['label' => 'Safe password',  'route' => 'settings.vault'],
                    ] as $key => $item)
                        <a href="{{ route($item['route']) }}"
                           class="block -ml-px pl-4 pr-3 py-2 text-sm border-l-2 {{ $section === $key
                               ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400 font-medium'
                               : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600' }}">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>
            </aside>

            {{-- Content --}}
            <div class="flex-1 max-w-lg">
                @if (session()->has('success'))
                    <pwdsafe-alert theme="success" classes="mb-4">
                        {{ session()->get('success') }}
                    </pwdsafe-alert>
                @endif
                @if ($errors->any())
                    <pwdsafe-alert theme="danger" classes="mb-4">
                        @foreach ($errors->all() as $error)
                            {{ $error }}
                        @endforeach
                    </pwdsafe-alert>
                @endif

                @if ($section === 'profile')
                    @if (is_null(auth()->user()->name))
                        <pwdsafe-alert theme="info" classes="mb-4">
                            You haven't set your display name yet. Add one below so others can find you when sharing credentials.
                        </pwdsafe-alert>
                    @endif
                    <form method="post" action="{{ route('settings') }}">
                        <div class="bg-white dark:bg-gray-700 rounded-md shadow">
                            <div class="px-8 py-6">
                                @csrf
                                <input type="hidden" name="change_type" value="profile">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ auth()->user()->email }}</p>
                                <pwdsafe-label class="mb-1" for="name">Display name</pwdsafe-label>
                                <pwdsafe-input type="text" name="name" id="name"
                                    value="{{ auth()->user()->name }}"
                                    placeholder="Your name"
                                    autocomplete="name"
                                ></pwdsafe-input>
                            </div>
                            <div class="flex justify-end gap-x-2 bg-gray-50 dark:bg-gray-700 dark:border-t dark:border-gray-800 px-8 py-4">
                                <pwdsafe-button type="submit">Save profile</pwdsafe-button>
                            </div>
                        </div>
                    </form>

                @elseif ($section === 'login')
                    @if ($isExternalAuth)
                        <pwdsafe-alert theme="info" classes="mb-4">
                            Your account uses {{ auth()->user()->auth_source === 'oidc' ? 'OIDC' : 'LDAP' }} authentication.
                            Login password management is handled by your identity provider.
                        </pwdsafe-alert>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            The password you use to sign in to this application. If you have set a separate safe
                            password, changing this does not affect access to your stored credentials.
                        </p>
                    @endif
                    <div class="{{ $isExternalAuth ? 'opacity-50 pointer-events-none select-none' : '' }}">
                        <form method="post" action="{{ route('settings') }}"
                            data-login-change
                            data-login-salt="{{ auth()->user()->login_salt }}"
                            data-vault-salt="{{ auth()->user()->privkey_salt }}"
                            data-separate="{{ auth()->user()->hasSeparateVaultPassword() ? 'true' : 'false' }}">
                            <fieldset {{ $isExternalAuth ? 'disabled' : '' }}>
                                <div class="bg-white dark:bg-gray-700 rounded-md shadow">
                                    <div class="px-8 py-6">
                                        @csrf
                                        <input type="hidden" name="change_type" value="login">
                                        <div class="mb-2">
                                            <pwdsafe-label class="mb-1" for="login_oldpwd">Current login password</pwdsafe-label>
                                            <pwdsafe-input type="password" name="oldpwd" id="login_oldpwd" autocomplete="off" required></pwdsafe-input>
                                        </div>
                                        <div class="mb-2">
                                            <pwdsafe-label class="mb-1" for="login_newpwd">New login password</pwdsafe-label>
                                            <pwdsafe-input type="password" name="password" id="login_newpwd" autocomplete="off" required></pwdsafe-input>
                                        </div>
                                        <div class="mb-2">
                                            <pwdsafe-label class="mb-1" for="login_newpwd_confirm">Confirm</pwdsafe-label>
                                            <pwdsafe-input type="password" name="password_confirmation" id="login_newpwd_confirm" autocomplete="off" required></pwdsafe-input>
                                        </div>
                                    </div>
                                    <div class="flex justify-end gap-x-2 bg-gray-50 dark:bg-gray-700 dark:border-t dark:border-gray-800 px-8 py-4">
                                        <pwdsafe-button type="submit">Change login password</pwdsafe-button>
                                    </div>
                                </div>
                            </fieldset>
                        </form>
                    </div>

                @elseif ($section === 'vault')
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        The password used to encrypt your credentials. It never leaves your browser — the server only
                        stores an encrypted copy of your vault. If you set this to something different from your login
                        password, you will be asked to enter it separately each time you sign in.
                    </p>
                    @if (!auth()->user()->canDecryptPrivkey())
                        <div class="bg-white dark:bg-gray-700 rounded-md shadow px-8 py-4 mb-4 text-gray-600 dark:text-gray-300">
                            <h4 class="text-xl mb-2 text-amber-600">Warning</h4>
                            <p class="mb-2">
                                We cannot decrypt your safe. This can happen after an admin password reset or an LDAP/AD password change.
                            </p>
                            <p class="mb-2">
                                To get access to your stored passwords again, re-enter your current safe password below.<br>
                                If you do not remember your safe password, you can <a href="/settings/resetaccount" class="underline">reset your account</a>. This will permanently delete all stored credentials.
                            </p>
                        </div>
                    @endif
                    <form method="post" action="{{ route('settings') }}"
                        data-vault-change
                        data-vault-salt="{{ auth()->user()->privkey_salt }}"
                        data-separate="{{ auth()->user()->hasSeparateVaultPassword() ? 'true' : 'false' }}"
                        @if (!config('ldap.enabled') && !auth()->user()->hasSeparateVaultPassword())
                            data-local-change
                        @endif>
                        <div class="bg-white dark:bg-gray-700 rounded-md shadow">
                            <div class="px-8 py-6">
                                @csrf
                                <input type="hidden" name="change_type" value="vault">
                                <div class="mb-2">
                                    <pwdsafe-label class="mb-1" for="vault_oldpwd">Current safe password</pwdsafe-label>
                                    <pwdsafe-input type="password" name="oldpwd" id="vault_oldpwd" autocomplete="off" required></pwdsafe-input>
                                </div>
                                <div class="mb-2">
                                    <pwdsafe-label class="mb-1" for="vault_newpwd">New safe password</pwdsafe-label>
                                    <pwdsafe-input type="password" name="password" id="vault_newpwd" autocomplete="off" required></pwdsafe-input>
                                </div>
                                <div class="mb-2">
                                    <pwdsafe-label class="mb-1" for="vault_newpwd_confirm">Confirm</pwdsafe-label>
                                    <pwdsafe-input type="password" name="password_confirmation" id="vault_newpwd_confirm" autocomplete="off" required></pwdsafe-input>
                                </div>
                            </div>
                            <div class="flex justify-end gap-x-2 bg-gray-50 dark:bg-gray-700 dark:border-t dark:border-gray-800 px-8 py-4">
                                <pwdsafe-button type="submit">Change safe password</pwdsafe-button>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    @vite('resources/js/changepwd.js')
@endpush
