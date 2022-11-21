@extends('layouts.master')
@section('content')
    <div class="container mx-auto">
        <h3 class="text-2xl mb-4">Reset account</h3>
        <pwdsafe-alert theme='danger' classes='max-w-2xl'>
            Are you sure you want to reset your account? This will:
            <ul class='ml-10 list-disc my-2'>
                <li>Delete all your secrets</li>
                <li>Delete all your group memberships (and groups if you are the only member)</li>
                <li>Regenerate a new private and public key pair</li>
            </ul>
            <div class='mb-2 font-bold'>This action cannot be undone!</div>
            <form method='post'>
                @csrf
                @method('delete')
                <pwdsafe-button type='submit' theme='danger'>Yes, I'm sure</pwdsafe-button>
            </form>
        </pwdsafe-alert>
    </div>
@endsection()
