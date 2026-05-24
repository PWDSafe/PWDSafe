@extends('layouts.master')
@section('content')
    <security-check
        :writable-groups="{{ auth()->user()->groupsWithWriteAccess->map->only('id', 'name') }}"
    ></security-check>
@endsection
