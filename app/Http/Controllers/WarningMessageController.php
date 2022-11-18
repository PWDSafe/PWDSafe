<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WarningMessageController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        abort_if(auth()->user()->warning_seen, Response::HTTP_CONFLICT);

        $request->validate([
            'accept' => ['present']
        ]);

        $user = auth()->user();
        $user->warning_seen = true;
        $user->save();

        return response()->json(['status' => 'OK']);
    }
}
