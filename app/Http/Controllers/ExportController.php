<?php

namespace App\Http\Controllers;

use App\Group;
use App\Helpers\Encryption;
use Symfony\Component\HttpFoundation\Response;

class ExportController extends Controller
{
    public function store(Group $group): \Illuminate\Http\Response
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
        $exportname = 'pwdsafe_export_' . $sanitized_group_name . '_' . date('Y-m-d') . '.json';

        $data = [];

        foreach ($credentials as $credential) {
            $pwd = $credential->encryptedcredentials[0];
            $pwddecoded = $encryption->decWithPriv(
                $pwd->data,
                $encryption->dec(auth()->user()->privkey, session()->get('password'))
            );
            $data[] = [
                'site' => $credential->site,
                'username' => $credential->username,
                'password' => $pwddecoded,
                'notes' => $credential->notes
            ];
        }

        return response()->make(
            json_encode($data, JSON_UNESCAPED_UNICODE),
            Response::HTTP_OK,
            [
                'Content-Type' => 'text/json',
                'Content-Disposition' => 'attachment; filename="' . $exportname . '"'
            ]
        );
    }
}
