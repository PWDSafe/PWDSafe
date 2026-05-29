@php $activeGroupId = $credential->groupid; @endphp
@extends('layouts.vault')
@section('content')
    <div>
        <pwdsafe-alert theme="danger" classes="max-w-2xl mx-auto">
            <p class="mb-4"><strong>Are you sure</strong> you wish to delete credential for "<strong>{{ $credential->site }}</strong>"?</p>
            <form method="post" action="{{ route('credential', $credential->id) }}">
                @method('delete')
                @csrf
                <pwdsafe-button theme="danger" type="submit" >Yes, I'm sure</pwdsafe-button>
            </form>
        </pwdsafe-alert>
    </div>
@endsection
