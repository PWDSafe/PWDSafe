@extends('layouts.master')
@section('content')
    <div class='inset-0 absolute flex items-center justify-center'>
        <div class='max-w-lg w-full bg-gray-700 mx-auto p-4 rounded'>
            <h1 class='text-xl mb-4'>A credential has been shared with you</h1>
            @if (!$verified)
                @if ($credential->burn_after_read)
                    <span class='text-red-500 block mb-4'>This credential will be deleted after viewed</span>
                @endif
                <form method='post' action='{{ route('shared.show', $credential->id) . '?token=' . $token }}'>
                    @csrf
                    <input type='hidden' name='verified' value='1'>
                    <pwdsafe-button theme='primary' type='submit'>Show credential</pwdsafe-button>
                </form>
            @endif
            @if ($verified)
                <div class='flex flex-col gap-4'>
                    <div>
                        <pwdsafe-label>
                            Site
                        </pwdsafe-label>
                        <pwdsafe-input value='{{ $credential->site }}' readonly></pwdsafe-input>
                    </div>
                    <div>
                        <pwdsafe-label>
                            Username
                        </pwdsafe-label>
                        <pwdsafe-input value='{{ $credential->username }}' readonly></pwdsafe-input>
                    </div>
                    <div>
                        <pwdsafe-label>
                            Password
                        </pwdsafe-label>

                        <pwdsafe-textarea readonly>{{ $secret }}</pwdsafe-textarea>
                    </div>
                    <div>
                        <pwdsafe-label>
                            Notes
                        </pwdsafe-label>

                        <pwdsafe-textarea readonly>{{ $credential->notes }}</pwdsafe-textarea>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
