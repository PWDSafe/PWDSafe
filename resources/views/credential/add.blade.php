@extends('layouts.master')

@section('content')
    <div class="container mx-auto">
        <div class="bg-white rounded-md shadow max-w-3xl">
            <form method="post">
                <div class="px-8 py-4">
                    <h3 class="text-2xl mb-4">Add credentials</h3>
                    @csrf
                    <div class="mb-4">
                        <div class="mb-2">
                            <pwdsafe-label class="mb-1" for="site">Site</pwdsafe-label>
                            <pwdsafe-input type="text" name="site" id="site" autocomplete="off" required
                                           autofocus></pwdsafe-input>
                        </div>
                        <div class="mb-2">
                            <pwdsafe-label class="mb-1" for="user">Username</pwdsafe-label>
                            <pwdsafe-input type="text" name="user" id="user" class="form-control"
                                           autocomplete="off" required></pwdsafe-input>
                        </div>
                        <div class="mb-2">
                            <pwdsafe-label class="mb-1" for="pass">Password</pwdsafe-label>
                            <pwdsafe-textarea name="pass" id="pass" rows="5" required></pwdsafe-textarea>
                        </div>
                        <div class="mb-2">
                            <pwdsafe-label class="mb-1" for="notes">Notes</pwdsafe-label>
                            <pwdsafe-textarea name="notes" id="notes" rows="5"></pwdsafe-textarea>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50">
                    <div class="flex justify-end gap-x-2 px-8 py-4">
                        <pwdsafe-button theme="secondary" btntype="a" href="{{ route('group', $group->id) }}">
                            Back
                        </pwdsafe-button>
                        <pwdsafe-button type="submit">Add credential</pwdsafe-button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
