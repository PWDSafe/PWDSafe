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

        $file = $request->file('csvfile');

        if (($fh = fopen($file->getRealPath(), 'r')) !== false) {
            while (($data = fgetcsv($fh)) !== false) {
                if (count($data) !== 4) {
                    # Seems malformed, skip this row
                    continue;
                }

                [$site, $username, $password, $note] = $data;

                if (strlen($site) === 0 || strlen($password) === 0) {
                    # Seems malformed, skip this row
                    continue;
                }

                Credential::addCredentials([
                    'creds' => $site,
                    'credu' => $username,
                    'credn' => $note,
                    'credp' => $password,
                    'currentgroupid' => $params['group'],
                ]);
            }
        }

        return redirect()->to('/groups/' . $params['group']);
    }
}
