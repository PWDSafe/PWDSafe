@extends('layouts.master')

@section('content')
    <div class="container mx-auto">
        <div class="flex justify-center mt-12 mb-8">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="text-gray-600 w-16 h-16">
                <path d="M18 1.5c2.9 0 5.25 2.35 5.25 5.25v3.75a.75.75 0 01-1.5 0V6.75a3.75 3.75 0 10-7.5 0v3a3 3 0 013 3v6.75a3 3 0 01-3 3H3.75a3 3 0 01-3-3v-6.75a3 3 0 013-3h9v-3c0-2.9 2.35-5.25 5.25-5.25z" />
            </svg>
        </div>
        <div id="vault-unlock-app" data-csrf="{{ csrf_token() }}"></div>
    </div>
@endsection
@push('scripts')
    @vite('resources/js/vault-unlock.js')
@endpush
