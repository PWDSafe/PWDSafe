@extends('layouts.master')
@section('content')
    <h1 class='text-2xl mb-4'>Shared credentials</h1>
    @forelse ($sharedcredentials as $sharedcredential)
        <div class='mb-4 bg-gray-700 p-4 rounded'>
            <div class='mb-2'>
                <span class='block text-xl'>{{ $sharedcredential->site }}</span>
                {{ $sharedcredential->username }}
            </div>
            <div class='flex justify-between items-center'>
                <div>
                    <span class='italic block'>Expires at {{ $sharedcredential->expire_at }}</span>
                    {{ $sharedcredential->burn_after_read ? 'Can be viewed once' : 'Can be viewed multiple times' }}<br>
                </div>
                <form method='post' action='{{ route('shared.show', $sharedcredential->id) }}'>
                    @method('DELETE')
                    @csrf
                    <pwdsafe-button theme='danger' type='submit'>Stop sharing</pwdsafe-button>
                </form>
            </div>
        </div>
    @empty
        <pwdsafe-alert classes='max-w-3xl'>
            <strong>No shared credentials found!</strong> You have not shared any credentials, or they have expired.
        </pwdsafe-alert>
    @endforelse
@endsection
