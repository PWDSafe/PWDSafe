<?php

namespace App\Http\Controllers;

use App\Helpers\Encryption;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Facades\DB;

class SecurityCheckController extends Eloquent
{
    public function index()
    {
        $sql = "SELECT CASE WHEN groups.id = users.primarygroup THEN 'Private' ELSE groups.name END AS groupname,
                        groups.id AS groupid, credentials.id, credentials.site, credentials.username, credentials.notes, encryptedcredentials.data AS pass FROM credentials
                        INNER JOIN `groups` ON credentials.groupid = groups.id
                        INNER JOIN usergroups ON groups.id = usergroups.groupid
                        INNER JOIN users ON usergroups.userid = users.id
                        INNER JOIN encryptedcredentials ON encryptedcredentials.credentialid = credentials.id
                        WHERE users.id = :userid AND encryptedcredentials.userid = users.id";
        $result = DB::select($sql, ['userid' => auth()->user()->id]);
        $encryption = app(Encryption::class);
        $data = [];
        foreach ($result as $row) {
            $pwd = $encryption->decWithPriv(
                $row->pass,
                $encryption->dec(auth()->user()->privkey, session()->get('password'))
            );
            $data[$pwd][] = [
                'groupname' => $row->groupname,
                'groupid' => $row->groupid,
                'notes' => $row->notes,
                'id' => $row->id,
                'site' => $row->site,
                'username' => $row->username
            ];
        }

        $hassame = [];
        foreach ($data as $pwd) {
            if (count($pwd) > 1) {
                $hassame[] = $pwd;
            }
        }

        return view('securitycheck', ['data' => $hassame]);
    }
}
