@php $activeGroupId = $group->id; @endphp
@extends('layouts.vault')
@section('content')
<div>
    <pwdsafe-alert theme="danger" classes="max-w-2xl mx-auto">
        <p class="mb-4"><strong>Are you sure</strong> you wish to delete group "<strong>{{ $group->name }}</strong>" and all of its saved credentials?</p>
        <form method="post" action="{{ route('group', $group->id) }}">
            @method('delete')
            @csrf
            <pwdsafe-button theme="danger" type="submit" >Yes, I'm sure</pwdsafe-button>
        </form>
    </pwdsafe-alert>
</div>
@endsection
