@extends('layouts.master')
@section('content')
    <security-check
        :writable-groups="{{ auth()->user()->groupsWithWriteAccess->map->only('id', 'name') }}"
        private-group-url="{{ route('group', auth()->user()->primarygroup) }}"
        groups-url="{{ route('groups') }}"
    ></security-check>
@endsection
