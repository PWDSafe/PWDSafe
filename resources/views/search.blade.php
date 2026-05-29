@php
    $activeGroupId = null;
    $credentialsWithGroupNames = $data->map(function ($cred) {
        $cred->display_group_name = auth()->user()->primarygroup === $cred->group->id
            ? 'Private'
            : $cred->group->name;
        return $cred;
    });
@endphp
@extends('layouts.vault')
@section('content')
<div>
    <h3 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-5">Search results</h3>

    @if ($data->count() > 0)
        <credential-table
            :credentials="{{ $credentialsWithGroupNames }}"
            :groups="{{ auth()->user()->groupsWithWriteAccess->map(fn ($g) => ['id' => $g->id, 'name' => $g->id === auth()->user()->primarygroup ? 'Private' : $g->name]) }}"
            :can-update="false"
            :show-group-name="true"
        ></credential-table>
    @else
        <pwdsafe-alert>
            <strong>No credentials found!</strong> Try searching for something else.
        </pwdsafe-alert>
    @endif
</div>
@endsection
