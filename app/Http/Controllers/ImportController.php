<?php

namespace App\Http\Controllers;

use App\Credential;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $params = $this->validate($request, [
            'group' => 'required',
        ]);

        abort_unless(auth()->user()->groups->contains('id', $params['group']), 403);

        $group = auth()->user()->groups->find($params['group']);
        $this->authorize('update', $group);

        $file = $request->file('jsonfile');

        $file = file_get_contents($file->getRealPath());
        $data = json_decode($file);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return redirect()
                ->back()
                ->withErrors([
                    'import_error' => 'Cannot parse file. Is it valid JSON?'
                ]);
        }

        if (!is_array($data)) {
            return redirect()
                ->back()
                ->withErrors([
                    'import_error' => 'JSON detected, but base element is not an array.'
                ]);
        }

        $count = 0;
        $skipped = 0;

        foreach ($data as $row) {
            if (
                !property_exists($row, 'site') ||
                !property_exists($row, 'username') ||
                !property_exists($row, 'password')
            ) {
                # Seems malformed, skip this row
                $skipped++;
                continue;
            }

            Credential::addCredentials([
                'creds' => $row->site,
                'credu' => $row->username,
                'credn' => $row->notes ?? '',
                'credp' => $row->password,
                'currentgroupid' => $params['group'],
            ]);

            $count++;
        }

        return redirect()
            ->back()
            ->with([
                'import_count' => $count,
                'import_skipped' => $skipped
            ]);
    }
}
