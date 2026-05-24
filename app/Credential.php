<?php

namespace App;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Credential extends Eloquent
{
    public $timestamps = false;
    protected $guarded = [];

    /** @return BelongsTo<Group, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'groupid');
    }

    /**
     * @param array<string, mixed> $params  Must include 'encrypted' array of ['userid' => int, 'data' => string]
     */
    public static function addCredentials(array $params): void
    {
        $credential = new Credential();
        $credential->groupid = $params['currentgroupid'];
        $credential->site = $params['creds'];
        $credential->username = $params['credu'];
        $credential->notes = $params['credn'];
        $credential->save();

        foreach ($params['encrypted'] as $entry) {
            $encrypted = new Encryptedcredential();
            $encrypted->credentialid = $credential->id;
            $encrypted->userid = $entry['userid'];
            $encrypted->data = $entry['data'];
            $encrypted->save();
        }
    }

    /**
     * @param array<string, mixed> $params  Must include 'encrypted' array of ['userid' => int, 'data' => string]
     */
    public static function updateCredentials(Credential $credential, array $params): void
    {
        $credential->site = $params['creds'];
        $credential->username = $params['credu'];
        $credential->notes = $params['credn'] ?? null;
        $credential->save();

        foreach ($params['encrypted'] as $entry) {
            Encryptedcredential::where('credentialid', $credential->id)
                ->where('userid', $entry['userid'])
                ->update(['data' => $entry['data']]);
        }
    }

    public function deleteCredential(): void
    {
        Encryptedcredential::where('credentialid', $this->id)->delete();
        $this->delete();
    }

    /** @return HasMany<Encryptedcredential, $this> */
    public function encryptedcredentials(): HasMany
    {
        return $this->hasMany(Encryptedcredential::class, 'credentialid');
    }
}
