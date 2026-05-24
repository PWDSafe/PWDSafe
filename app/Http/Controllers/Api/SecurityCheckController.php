<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class SecurityCheckController extends Controller
{
    /**
     * Return all of the authenticated user's credentials (with ciphertext) for client-side security check.
     */
    public function index(): Response|Application|ResponseFactory
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            $sql = "SELECT CASE WHEN `groups`.id = users.primarygroup THEN 'Private' ELSE `groups`.name END AS groupname,
                        `groups`.id AS groupid, credentials.id, credentials.site, credentials.username, credentials.notes, encryptedcredentials.data FROM credentials
                        INNER JOIN `groups` ON credentials.groupid = `groups`.id
                        INNER JOIN usergroups ON `groups`.id = usergroups.groupid
                        INNER JOIN users ON usergroups.userid = users.id
                        INNER JOIN encryptedcredentials ON encryptedcredentials.credentialid = credentials.id
                        WHERE users.id = :userid AND encryptedcredentials.userid = users.id";
        } else {
            $sql = "SELECT CASE WHEN \"groups\".id = users.primarygroup THEN 'Private' ELSE \"groups\".name END AS groupname,
                        \"groups\".id AS groupid, credentials.id, credentials.site, credentials.username, credentials.notes, encryptedcredentials.data FROM credentials
                        INNER JOIN \"groups\" ON credentials.groupid = \"groups\".id
                        INNER JOIN usergroups ON \"groups\".id = usergroups.groupid
                        INNER JOIN users ON usergroups.userid = users.id
                        INNER JOIN encryptedcredentials ON encryptedcredentials.credentialid = credentials.id
                        WHERE users.id = :userid AND encryptedcredentials.userid = users.id";
        }

        $rows = DB::select($sql, ['userid' => auth()->user()->id]);

        return response(array_map(fn ($row) => [
            'id' => $row->id,
            'site' => $row->site,
            'username' => $row->username,
            'notes' => $row->notes,
            'groupname' => $row->groupname,
            'groupid' => $row->groupid,
            'data' => $row->data,
        ], $rows));
    }
}
