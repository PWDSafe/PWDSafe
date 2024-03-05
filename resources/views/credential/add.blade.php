@extends('layouts.master')

@section('content')
    <div class="container mx-auto">
        <div class="bg-white dark:bg-gray-700 rounded-md shadow max-w-3xl overflow-hidden">
            <add-credentials-form backlink='{{ route('group', $group->id) }}'></add-credentials-form>
        </div>
    </div>
@endsection
