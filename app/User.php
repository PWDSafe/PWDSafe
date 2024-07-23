<?php

namespace App;

use App\Helpers\Encryption;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

/**
 * @property ?string $two_fa_secret
 */
class User extends Authenticatable
{
    use Notifiable;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'lastlogin' => 'datetime',
        'primarygroup' => 'integer',
    ];

    public bool $ldap = false;

    /**
     * @return BelongsToMany<Group>
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'usergroups', 'userid', 'groupid')
            ->orderBy('name')
            ->withPivot('permission')
            ->withCount('users')
            ->withCount('credentials');
    }

    /**
     * @return BelongsToMany<Group>
     */
    public function groupsWithWriteAccess(): BelongsToMany
    {
        return $this->groups()->wherePivotIn('permission', ['write', 'admin']);
    }

    public function changePassword(string $newpass): void
    {
        // Generate new public and private key
        $enc = new Encryption();
        list($privKey, $pubKey) = $enc->genNewKeys();

        // Loop through all credentials for this user and reencrypt them with the new private key
        $this->updateEncryptedCredentials(session()->get('password'), $pubKey, $enc);

        // Encrypt private key with new password
        $encryptedprivkey = $enc->enc($privKey, $newpass);

        // Update users-table with the new password (hashed) and the private key (encrypted)
        $this->password = Hash::make($newpass);
        $this->pubkey = $pubKey;
        $this->privkey = $encryptedprivkey;
        $this->save();

        session()->put('password', $newpass);
    }

    /**
     * @param string $currentpass
     * @param string $newPubKey
     * @param Encryption $enc
     */
    private function updateEncryptedCredentials(string $currentpass, string $newPubKey, Encryption $enc): void
    {
        $encryptedcredentials = Encryptedcredential::where('userid', $this->id)->get();
        foreach ($encryptedcredentials as $credential) {
            $data = $enc->decWithPriv($credential->data, $enc->dec($this->privkey, $currentpass));
            $newdata = $enc->encWithPub($data, $newPubKey);
            $credential->data = $newdata;
            $credential->save();
        }
    }

    public static function registerUser(string $username, string $password): void
    {
        $enc = app(Encryption::class);
        list($privKey, $pubKey) = $enc->genNewKeys();
        $privKey = $enc->enc($privKey, $password);

        $group = new Group();
        $group->name = $username;
        $group->save();

        $user = new User();
        $user->email = $username;
        $user->password = Hash::make($password);
        $user->pubkey = $pubKey;
        $user->privkey = $privKey;
        $user->primarygroup = $group->id;
        $user->save();

        $user->groups()->attach($group);
    }

    public function canDecryptPrivkey(string $password): bool
    {
        return strlen(app(Encryption::class)->dec($this->privkey, $password)) !== 0;
    }

    /**
     * @return HasMany<SharedCredential>
     */
    public function sharedCredentials(): HasMany
    {
        return $this->hasMany(SharedCredential::class);
    }
}
