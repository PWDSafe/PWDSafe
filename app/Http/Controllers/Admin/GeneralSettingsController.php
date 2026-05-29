<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\SystemSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GeneralSettingsController extends Controller
{
    public function index(): View
    {
        $settings = [
            'registration_enabled' => (bool) SystemSetting::get('registration_enabled', true),
            'private_groups_shareable' => (bool) SystemSetting::get('private_groups_shareable', false),
        ];

        return view('admin.settings.general', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'registration_enabled' => ['nullable', 'boolean'],
            'private_groups_shareable' => ['nullable', 'boolean'],
        ]);

        SystemSetting::set('registration_enabled', $validated['registration_enabled'] ?? false);
        SystemSetting::set('private_groups_shareable', $validated['private_groups_shareable'] ?? false);

        return redirect()->back()->with('success', 'Settings saved.');
    }
}
