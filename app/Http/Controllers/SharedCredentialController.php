<?php

namespace App\Http\Controllers;

use App\Credential;
use App\Helpers\Encryption;
use App\SharedCredential;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SharedCredentialController extends Controller
{
    public function __construct(public Encryption $encryption)
    {
        SharedCredential::deleteExpired();
    }

    public function index(): Factory|View|Application
    {
        $sharedcredentials = auth()->user()->sharedCredentials()->get();

        return view('shared.index', compact('sharedcredentials'));
    }

    public function destroy(SharedCredential $credential): RedirectResponse
    {
        abort_if($credential->user_id !== auth()->id(), 403);
        $credential->delete();

        return redirect()->back();
    }

    public function store(Credential $credential, Request $request): JsonResponse
    {
        $this->authorize('view', $credential);
        $attributes = $request->validate([
            'expire_at' => ['required', 'date', 'after:today', 'before:' . Carbon::now()->addMonth()->addDay()],
            'burn_after_read' => ['required', 'boolean'],
            'secret' => ['required', 'string'],
            'token' => ['required', 'string', 'size:40'],
        ]);

        $shared_credential = SharedCredential::create([
            'expire_at' => $attributes['expire_at'],
            'burn_after_read' => $attributes['burn_after_read'],
            'name' => $credential->name,
            'url' => $credential->url,
            'username' => $credential->username,
            'notes' => $credential->notes,
            'secret' => $attributes['secret'],
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'status' => 'OK',
            'url' => route('shared.show', $shared_credential->id) . '?token=' . $attributes['token'],
        ]);
    }

    public function show(SharedCredential $credential, Request $request): Factory|View|Application
    {
        $attributes = $request->validate([
            'token' => ['required', 'string'],
            'verified' => ['sometimes', 'required', 'boolean']
        ]);
        $token = $attributes['token'];

        $secret = $this->encryption->dec($credential->secret, $token);
        abort_if(strlen($secret) === 0, 404);

        $verified = $attributes['verified'] ?? false;

        if ($credential->burn_after_read && $verified) {
            $credential->delete();
        }

        return view('shared.show', compact('token', 'credential', 'verified', 'secret'));
    }
}
