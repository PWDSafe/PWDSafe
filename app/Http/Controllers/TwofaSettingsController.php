<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FAQRCode\Google2FA;

class TwofaSettingsController extends Controller
{
    public function index(Google2FA $google2fa): Factory|View|Application
    {
        if (is_null(auth()->user()->two_fa_secret) && !session()->has('2fa_secret')) {
            session()->put('2fa_secret', $google2fa->generateSecretKey());
        }

        $totpUrl = $google2fa->getQRCodeUrl(
            config('app.url'),
            auth()->user()->email,
            session()->get('2fa_secret')
        );

        $qrcodeinline = $google2fa->getQRCodeInline(
            config('app.url'),
            auth()->user()->email,
            session()->get('2fa_secret')
        );

        return view('settings.twofa.index', compact('qrcodeinline', 'totpUrl'));
    }

    public function store(Request $request, Google2FA $google2fa): RedirectResponse
    {
        $params = $request->validate([
            'currentpassword' => ['required', 'string'],
            'otpcode' => ['required', 'string']
        ]);

        if (!Hash::check($params['currentpassword'], auth()->user()->password)) {
            return redirect()->back()->withErrors(['currentpassword' => 'Does not match your current password']);
        }

        if (!$google2fa->verify($params['otpcode'], session()->get('2fa_secret'))) {
            return redirect()->back()->withErrors(['otpcode' => 'One time code not valid']);
        }

        auth()->user()->two_factor_secret = encrypt(session()->get('2fa_secret'));
        auth()->user()->save();

        return redirect()
            ->back()
            ->with('success', 'You have successfully added two factor authentication to your account.');
    }

    public function destroy(Request $request, Google2FA $google2fa): RedirectResponse
    {
        $params = $request->validate([
            'currentpassword' => ['required', 'string'],
            'otpcode' => ['required', 'string']
        ]);

        if (!Hash::check($params['currentpassword'], auth()->user()->password)) {
            return redirect()->back()->withErrors(['currentpassword' => 'Does not match your current password']);
        }

        if (!$google2fa->verify($params['otpcode'], decrypt(auth()->user()->two_factor_secret))) {
            return redirect()->back()->withErrors(['otpcode' => 'One time code not valid']);
        }

        auth()->user()->two_factor_secret = null;
        auth()->user()->save();

        return redirect()
            ->back()
            ->with('success', 'You have successfully disabled two factor authentication for your account.');
    }
}
