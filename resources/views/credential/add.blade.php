@extends('layouts.master')

@section('content')
    <div class="container mx-auto">
        <h3 class="text-2xl mb-4">Add credentials</h3>
        <form method="post" class="max-w-3xl">
            @csrf
            <div class="mb-4">
                <div class="mb-2">
                    <pwdsafe-label class="mb-1" for="site">Site</pwdsafe-label>
                    <pwdsafe-input type="text" name="site" id="site" autocomplete="off" required></pwdsafe-input>
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
            <div class="flex justify-between">
                <pwdsafe-button theme="secondary" btntype="a" href="{{ route('group', $group->id) }}">
                    Back
                </pwdsafe-button>
                <pwdsafe-button type="submit">Add credential</pwdsafe-button>
            </div>
        </form>
    </div>
@endsection
