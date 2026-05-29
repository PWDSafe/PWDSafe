@php $activeGroupId = $group->id; @endphp
@extends('layouts.vault')

@section('content')
    <div class="bg-white dark:bg-gray-700 rounded-md shadow max-w-3xl overflow-hidden">
        <add-credentials-form backlink='{{ route('group', $group->id) }}' :groupid="{{ $group->id }}"></add-credentials-form>
    </div>
@endsection
