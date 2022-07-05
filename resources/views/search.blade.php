@extends('layouts.master')
@section('content')
<div class="container">
    <div class="clearfix">
        <div class="" style="margin-bottom: 20px">
            <h3 class="text-2xl">Search</h3>
        </div>
    </div>
    @if ($data->count() > 0)
    <div class="flex flex-wrap">
        @foreach ($data as $row)
            <credential-card :credential="{{ $row }}" :showgroupname="true" :groups="{{ auth()->user()->groups->map->only('id', 'name') }}" groupname="{{ auth()->user()->primarygroup === $row->group->id ? 'Private' : $row->group->name }}"></credential-card>
        @endforeach
    </div>
    @else
    <div class="alert alert-info" role="alert">
        <strong>No credentials found!</strong> Try searching for something else.
    </div>
    @endif
</div>
@endsection
