<?php

namespace App;

use App\Helpers\Encryption;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Credential extends Eloquent
{
    public $timestamps = false;

    public function group()
    {
        return $this->belongsTo(Group::class, 'groupid');
    }

    public static function addCredentials($params)
    {
        $credential = new Credential;
        $credential->groupid = $params['currentgroupid'];
        $credential->site = $params['creds'];
        $credential->username = $params['credu'];
        $credential->notes = $params['credn'];
        $credential->save();

        $group = \App\Group::where('id', $params['currentgroupid'])->first();
        $users = $group->users()->get()->pluck('pubkey', 'id');

        foreach ($users as $userid => $pubkey) {
            $encrypted = new Encryptedcredential;
            $encrypted->credentialid = $credential->id;
            $encrypted->userid = $userid;
            $encrypted->data = app(Encryption::class)->encWithPub($params['credp'], $pubkey);
            $encrypted->save();
        }
    }

    public static function updateCredentials(Credential $credential, $params)
    {
        $credential->site = $params['creds'];
        $credential->username = $params['credu'];
        $credential->notes = $params['credn'] ?? null;
        $credential->save();

        $allpublic = $credential->group->users()->get(['pubkey', 'userid'])->keyBy('userid')->toArray();
        $allencrypted = Encryptedcredential::where('credentialid', $credential->id)->get();
        foreach ($allencrypted as $encrypted) {
            $encrypted->data = app(Encryption::class)->encWithPub($params['credp'], $allpublic[$encrypted->userid]['pubkey']);
            $encrypted->save();
        }
    }

    public function deleteCredential()
    {
        Encryptedcredential::where('credentialid', $this->id)->delete();
        $this->delete();
    }

    public function encryptedcredentials()
    {
        return $this->hasMany(Encryptedcredential::class, 'credentialid');
    }
}
