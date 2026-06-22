@extends('layouts.master')
@section('content')
    <div class="container mx-auto">
        <h3 class="text-2xl mb-6">Administration</h3>

        <div class="flex flex-col md:flex-row gap-10 md:items-start">
            {{-- Sidebar nav --}}
            @include('admin._sidebar')

            {{-- Content --}}
            <div class="flex-1">
                @if (session()->has('success'))
                    <pwdsafe-alert theme="success" classes="mb-4">
                        {{ session()->get('success') }}
                    </pwdsafe-alert>
                @endif
                @if (session()->has('error'))
                    <pwdsafe-alert theme="danger" classes="mb-4">
                        {{ session()->get('error') }}
                    </pwdsafe-alert>
                @endif

                <admin-users
                    :users="{{ json_encode($users->map(fn($u) => [
                        'id' => $u->id,
                        'name' => $u->name,
                        'email' => $u->email,
                        'auth_source' => $u->auth_source,
                        'is_admin' => $u->is_admin,
                        'last_login_at' => $u->last_login_at,
                    ])) }}"
                    csrf="{{ csrf_token() }}"
                ></admin-users>
            </div>
        </div>
    </div>
@endsection()
