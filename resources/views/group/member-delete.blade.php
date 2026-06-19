@php $activeGroupId = $group->id; @endphp
@extends('layouts.vault')
@section('content')
<div>
    <pwdsafe-alert theme="danger" classes="max-w-2xl mx-auto">
        <p class="mb-4"><strong>Are you sure</strong> you wish to remove member <strong>{{ $user->name ?? $user->email }}</strong> from group "<strong>{{ $group->name }}</strong>"?</p>
        <form method="post" action="{{ route('groupMemberDelete', [$group->id, $user->id]) }}">
            @method('delete')
            @csrf
            <input type="hidden" name="userid" value="{{ $user->id }}">
            <pwdsafe-button theme="danger" type="submit">Yes, remove member</pwdsafe-button>
        </form>
    </pwdsafe-alert>
</div>
@endsection
