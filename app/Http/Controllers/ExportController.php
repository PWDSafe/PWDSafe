<?php

namespace App\Http\Controllers;

use App\Group;
use App\Helpers\Encryption;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function store(Group $group): StreamedResponse
    {
        abort_unless(auth()->user()->groups->contains('id', $group->id), 403);
        $credentials = $group->credentials()->withWhereHas('encryptedcredentials', function ($query) {
            $query->where('userid', auth()->user()->id);
        })->lazy();

        $encryption = app(Encryption::class);
        $sanitized_group_name = str_replace(" ", "_", $group->name);
        $sanitized_group_name = mb_ereg_replace("([^\w\s\d\-~,;\[\]\(\).])", '', $sanitized_group_name);
        $sanitized_group_name = mb_ereg_replace("([\.]{2,})", '', $sanitized_group_name);
        $sanitized_group_name = substr($sanitized_group_name, 0, 200);
        $exportname = 'pwdsafe_export_' . $sanitized_group_name . '_' . date('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($credentials, $encryption) {
            $out = fopen('php://output', 'w+');
            fputcsv($out, [
                __("Site"),
                __("Username"),
                __("Password"),
                __("Notes"),
            ]);

            foreach ($credentials as $credential) {
                $pwd = $credential->encryptedcredentials[0];
                $pwddecoded = $encryption->decWithPriv(
                    $pwd->data,
                    $encryption->dec(auth()->user()->privkey, session()->get('password'))
                );
                fputcsv($out, [
                    $credential->site,
                    $credential->username,
                    $pwddecoded,
                    $credential->notes
                ]);
            }
        }, $exportname, ['Content-Type' => 'text/csv']);
    }
}
