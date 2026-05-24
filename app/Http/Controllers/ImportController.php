<?php

namespace App\Http\Controllers;

use App\Credential;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'group' => 'required|integer',
            'credentials' => 'present|array',
            'credentials.*.site' => 'required|string',
            'credentials.*.username' => 'required|string',
            'credentials.*.notes' => 'nullable|string',
            'credentials.*.encrypted' => 'required|array',
            'credentials.*.encrypted.*.userid' => 'required|integer',
            'credentials.*.encrypted.*.data' => 'required|string',
        ]);

        $group = auth()->user()->groups->find($validated['group']);
        abort_unless($group !== null, 403);
        $this->authorize('update', $group);

        foreach ($validated['credentials'] as $row) {
            Credential::addCredentials([
                'creds' => $row['site'],
                'credu' => $row['username'],
                'credn' => $row['notes'] ?? '',
                'encrypted' => $row['encrypted'],
                'currentgroupid' => $validated['group'],
            ]);
        }

        return response()->json(['count' => count($validated['credentials'])]);
    }
}
