@extends('layouts.master')
@section('content')
    <div class="container mx-auto">
        <h3 class="text-2xl mb-6">Administration</h3>

        <div class="flex flex-col md:flex-row gap-10 md:items-start">
            {{-- Sidebar nav --}}
            @include('admin._sidebar')

            {{-- Content --}}
            <div class="flex-1 max-w-2xl">
                @if (session()->has('success'))
                    <pwdsafe-alert theme="success" classes="mb-4">
                        {{ session()->get('success') }}
                    </pwdsafe-alert>
                @endif

                @if ($errors->any())
                    <pwdsafe-alert theme="danger" classes="mb-4">
                        @foreach($errors->all() as $error)
                            {{ $error }}<br>
                        @endforeach
                    </pwdsafe-alert>
                @endif

                <admin-general-settings
                    :settings="{{ json_encode($settings) }}"
                    csrf="{{ csrf_token() }}"
                ></admin-general-settings>
            </div>
        </div>
    </div>
@endsection()
