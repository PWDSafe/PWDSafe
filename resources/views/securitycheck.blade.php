@extends('layouts.master')
@section('content')
    <div class="container">
        <div class="mb-12 max-w-3xl">
            <h3 class="text-2xl mb-2">
                Security check
            </h3>
            <p>The security check groups credentials that share the same password together. Consider changing the
                passwords for one or several credentials in each group to make sure that you use an unique password for
                each application/site.</p>
        </div>
        @if (count($data) > 0)
            @foreach ($data as $group)
                <div class="shadow-md mb-8 rounded-md bg-white dark:bg-gray-600">
                    <h5 class="text-lg border-b dark:border-gray-800 px-4 py-3 bg-gray-300 dark:bg-gray-700 rounded-t-md">Password group</h5>
                    <div class="px-2 py-3">
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                            @foreach ($group as $row)
                                <credential-card
                                    :credential="{{ json_encode($row) }}"
                                    :groups="{{ auth()->user()->groups }}"
                                    :showgroupname="true"
                                    groupname="{{ auth()->user()->primarygroup === $row['groupid'] ? 'Private' : $row['groupname'] }}"
                                ></credential-card>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <pwdsafe-alert theme="success" classes="max-w-3xl">
                <strong>No credentials found!</strong> This means that your credentials all have different passwords.
            </pwdsafe-alert>
        @endif
    </div>
@endsection
