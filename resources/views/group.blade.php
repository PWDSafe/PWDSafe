@extends('layouts.master')
@section('content')
<div class="container">
    <div class="clearfix">
        <div class="flex justify-between mb-5">
            <h3 class="text-2xl">
                @if ($group->id !== auth()->user()->primarygroup)
                    {{ $group->name }}
                @else
                    Private
                @endif
            </h3>
            <div class="flex items-start">
                @can('update', $group)
                <pwdsafe-button href="{{ route('addCredentials', $group->id) }}" classes="mr-2 flex items-center">
                    <heroicons-plus-icon class="h-5 w-5 mr-1"></heroicons-plus-icon> Add
                </pwdsafe-button>
                @endcan
                @can('view', $group)
                <form method="post" action="{{ route('export', $group->id) }}">
                    @csrf
                    <pwdsafe-button classes="mr-2 flex items-center" theme="secondary">
                        <heroicons-arrow-down-on-square-icon class="h-5 w-5 mr-1"></heroicons-arrow-down-on-square-icon>
                        Export
                    </pwdsafe-button>
                </form>
                @endcan
                @can('update', $group)
                <pwdsafe-modal>
                    <template v-slot:trigger="{ openModal }">
                        <pwdsafe-button theme="secondary" class="flex items-center" @click='openModal'>
                            <heroicons-arrow-up-on-square-icon class="h-5 w-5 mr-1"></heroicons-arrow-up-on-square-icon>
                            Import
                        </pwdsafe-button>
                    </template>
                    <template v-slot:default>
                        <h3 class="text-2xl mb-4">Import credentials</h3>
                        <p>Import a csv file with the following format:</p>
                        <pre class="my-2">site,username,password,notes</pre>
                        <p class="text-red-500 mb-4">Warning: Malformed rows will be skipped.</p>
                        <form method="post" action="/import" id="creduploadform" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="group" value="{{ $group->id }}">
                            <div class="form-group">
                                <input type="file" name="csvfile" id="csvfile" required>
                            </div>
                            <div class="flex justify-end mt-8">
                                <pwdsafe-button type="submit" classes="w-full">Import</pwdsafe-button>
                            </div>
                        </form>
                    </template>
                </pwdsafe-modal>
                @endcan
                @can('administer', $group)
                    <dropdown-menu>
                        <template #trigger>
                            <span class="h-full flex items-center border text-gray-600 border-gray-600 hover:bg-gray-600 hover:text-gray-100 px-4 py-1 rounded transition duration-200 ml-2 dark:text-gray-400 dark:border-gray-400 dark:hover:border-gray-600 dark:hover:bg-gray-600 dark:hover:text-gray-200">
                                <heroicons-cog-6-tooth-icon class="h-5 w-5"></heroicons-cog-6-tooth-icon>
                            </span>
                        </template>
                        <template #default>
                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                <dropdown-link href="/groups/{{ $group->id }}/name">Change name</dropdown-link>
                                <dropdown-link href="/groups/{{ $group->id }}/members">Manage members</dropdown-link>
                                <div class="my-1 border-b dark:border-gray-800"></div>
                                <dropdown-link href="/groups/{{ $group->id }}/delete" class="flex items-center gap-x-1">
                                    <heroicons-trash-icon class="h-5 w-5"></heroicons-trash-icon> Delete
                                </dropdown-link>
                            </div>
                        </template>
                    </dropdown-menu>
                @endcan
            </div>
        </div>
    </div>
    @if ($credentials->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach($credentials as $credential)
            <credential-card
                :credential="{{ $credential }}"
                :groups="{{ auth()->user()->groups->map->only('id', 'name') }}"
                :can-update="{{ auth()->user()->can('update', $group) ? 'true' : 'false' }}"
            ></credential-card>
        @endforeach
    </div>
    @else
        <pwdsafe-alert>
            <strong>No credentials!</strong> You can add some if you'd like.
        </pwdsafe-alert>
    @endif
</div>
@endsection('content')
