@php $activeGroupId = null; @endphp
@extends('layouts.vault')
@section('content')
    <security-check
        :writable-groups="{{ auth()->user()->groupsWithWriteAccess->map(fn ($g) => ['id' => $g->id, 'name' => $g->id === auth()->user()->primarygroup ? 'Private' : $g->name]) }}"
        private-group-url="{{ route('group', auth()->user()->primarygroup) }}"
        groups-url="{{ route('groups') }}"
    ></security-check>
@endsection
