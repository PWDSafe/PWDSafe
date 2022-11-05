<?php

namespace App;

use App\Helpers\Encryption;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Credential extends Eloquent
{
    public $timestamps = false;

    /**
     * @return BelongsTo<Group, Credential>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'groupid');
    }

    /**
     * @param array<string, string|int|null> $params
     */
    public static function addCredentials(array $params): void
    {
        $credential = new Credential();
        $credential->groupid = $params['currentgroupid'];
        $credential->site = $params['creds'];
        $credential->username = $params['credu'];
        $credential->notes = $params['credn'];
        $credential->save();

        $group = \App\Group::where('id', $params['currentgroupid'])->first();
        $users = $group->users()->pluck('pubkey', 'users.id');

        foreach ($users as $userid => $pubkey) {
            $encrypted = new Encryptedcredential();
            $encrypted->credentialid = $credential->id;
            $encrypted->userid = $userid;
            $encrypted->data = app(Encryption::class)->encWithPub($params['credp'], $pubkey);
            $encrypted->save();
        }
    }

    /**
     * @param array<string, string|int|null> $params
     */
    public static function updateCredentials(Credential $credential, array $params): void
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

    public function deleteCredential(): void
    {
        Encryptedcredential::where('credentialid', $this->id)->delete();
        $this->delete();
    }

    /**
     * @return HasMany<Encryptedcredential>
     */
    public function encryptedcredentials(): HasMany
    {
        return $this->hasMany(Encryptedcredential::class, 'credentialid');
    }
}
