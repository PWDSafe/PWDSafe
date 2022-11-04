<?php

namespace App;

use App\Helpers\Encryption;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

/**
 * @property string $two_fa_secret
 */
class User extends Authenticatable
{
    use Notifiable;
    use HasFactory;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'lastlogin' => 'datetime',
        'primarygroup' => 'integer',
    ];

    public $ldap = false;

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'usergroups', 'userid', 'groupid');
    }

    public function changePassword($newpass)
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
     * @param $currentpass
     * @param $newPubKey
     * @param Encryption $enc
     */
    private function updateEncryptedCredentials($currentpass, $newPubKey, Encryption $enc)
    {
        $encryptedcredentials = Encryptedcredential::where('userid', $this->id)->get();
        foreach ($encryptedcredentials as $credential) {
            $data = $enc->decWithPriv($credential->data, $enc->dec($this->privkey, $currentpass));
            $newdata = $enc->encWithPub($data, $newPubKey);
            $credential->data = $newdata;
            $credential->save();
        }
    }

    public static function registerUser($username, $password)
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
}
