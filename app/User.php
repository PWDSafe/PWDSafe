<?php

namespace App;

use App\Helpers\Encryption;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property ?string $two_fa_secret
 */
class User extends Authenticatable
{
    use HasApiTokens;
    use Notifiable;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     */
    protected $fillable = [
        'email', 'password', 'name', 'auth_source',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'primarygroup' => 'integer',
        'is_admin' => 'boolean',
    ];

    public bool $ldap = false;

    /** @return BelongsToMany<Group, $this> */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'usergroups', 'userid', 'groupid')
            ->orderBy('name')
            ->withPivot('permission')
            ->withCount('users')
            ->withCount('credentials');
    }

    /** @return BelongsToMany<Group, $this> */
    public function groupsWithWriteAccess(): BelongsToMany
    {
        return $this->groups()->wherePivotIn('permission', ['write', 'admin']);
    }

    public function isV2Format(): bool
    {
        return !is_null($this->privkey_salt);
    }

    public function isVaultConfigured(): bool
    {
        return (bool) $this->vault_configured;
    }

    public function hasSeparateVaultPassword(): bool
    {
        return (bool) $this->separate_vault_password;
    }

    /**
     * Decrypt the user's RSA private key using the vault_key from the session.
     * Falls back to the legacy session password for users not yet migrated.
     */
    public function decryptPrivkey(): string
    {
        if (is_null($this->privkey)) {
            return '';
        }

        $enc = app(Encryption::class);

        if ($this->isV2Format()) {
            return $enc->decV2($this->privkey, hex2bin(session('vault_key', '')));
        }

        return $enc->dec($this->privkey, session('password', ''));
    }

    /**
     * Returns true when the vault is unlocked.
     * For v2 (login_hash) users this is determined by the session flag set on login.
     * For v1 / LDAP users the server-held vault_key is used to verify decryption.
     */
    public function canDecryptPrivkey(): bool
    {
        if ($this->uses_login_hash) {
            return session('vault_unlocked', false);
        }

        return strlen($this->decryptPrivkey()) > 0;
    }

    /**
     * Migrate to the v2 privkey format (if needed) and set session('vault_key').
     *
     * If the password cannot decrypt the legacy privkey (e.g. an LDAP password
     * has changed), migration is skipped. canDecryptPrivkey() will return false,
     * which causes LoginController to redirect to the change-password page.
     */
    public function setupVaultSession(string $password): void
    {
        $enc = app(Encryption::class);

        if (!$this->isV2Format() && !is_null($this->privkey)) {
            $privkey = $enc->dec($this->privkey, $password);
            if (strlen($privkey) > 0) {
                $salt = bin2hex(random_bytes(32));
                $vaultKey = Encryption::deriveVaultKey($password, $salt);
                $this->privkey = $enc->encV2($privkey, $vaultKey);
                $this->privkey_salt = $salt;
                $this->save();
            }
            // If decryption failed (LDAP password changed), skip migration.
            // The vault_key below won't decrypt the privkey; canDecryptPrivkey()
            // returns false and the user is sent to the change-password page.
        }

        // Derive vault_key from current salt (or a temporary one if still legacy).
        $salt = $this->privkey_salt ?? bin2hex(random_bytes(32));
        $vaultKey = Encryption::deriveVaultKey($password, $salt);
        session()->put('vault_key', bin2hex($vaultKey));
    }

    public function changePassword(string $newLoginHash, string $newEncryptedPrivkey, string $newSalt): void
    {
        $this->password = Hash::make($newLoginHash);
        $this->privkey = $newEncryptedPrivkey;
        $this->privkey_salt = $newSalt;
        $this->uses_login_hash = true;
        $this->save();
    }

    /**
     * Create a user from client-provided cryptographic data (production registration path).
     * The server never sees the raw password, RSA private key, or vault key.
     */
    public static function registerFromClientData(
        string $email,
        string $loginHash,
        string $encryptedPrivKey,
        string $privkeySalt,
        string $pubKey,
    ): void {
        DB::transaction(function () use ($email, $loginHash, $encryptedPrivKey, $privkeySalt, $pubKey) {
            $group = new Group();
            $group->name = $email;
            $group->save();

            $user = new User();
            $user->email = $email;
            $user->password = Hash::make($loginHash);
            $user->pubkey = $pubKey;
            $user->privkey = $encryptedPrivKey;
            $user->privkey_salt = $privkeySalt;
            $user->uses_login_hash = true;
            $user->vault_configured = true;
            $user->auth_source = 'local';
            $user->primarygroup = $group->id;
            $user->save();

            $user->groups()->attach($group);
        });
    }

    public static function registerUser(string $username, string $password): void
    {
        $enc = app(Encryption::class);
        [$privKey, $pubKey] = $enc->genNewKeys();

        $salt = bin2hex(random_bytes(32));
        $vaultKey = Encryption::deriveVaultKey($password, $salt);
        $encryptedPrivKey = $enc->encV2($privKey, $vaultKey);

        DB::transaction(function () use ($username, $password, $pubKey, $encryptedPrivKey, $salt) {
            $group = new Group();
            $group->name = $username;
            $group->save();

            $user = new User();
            $user->email = $username;
            $user->password = Hash::make($password);
            $user->pubkey = $pubKey;
            $user->privkey = $encryptedPrivKey;
            $user->privkey_salt = $salt;
            $user->vault_configured = true;
            $user->primarygroup = $group->id;
            $user->save();

            $user->groups()->attach($group);
        });
    }

    /**
     * Create a local user without vault keys (admin-created account).
     * The user will be routed to vault setup on first login where they choose
     * their own vault password, which then becomes their login credential.
     */
    public static function createPendingLocalUser(string $email, string $password, ?string $name = null): User
    {
        return DB::transaction(function () use ($email, $password, $name) {
            $group = new Group();
            $group->name = $email;
            $group->save();

            $user = new User();
            $user->email = $email;
            $user->name = $name;
            $user->password = Hash::make($password);
            $user->vault_configured = false;
            $user->auth_source = 'local';
            $user->uses_login_hash = false;
            $user->primarygroup = $group->id;
            $user->save();

            $user->groups()->attach($group);

            return $user;
        });
    }

    /** @return HasMany<SharedCredential, $this> */
    public function sharedCredentials(): HasMany
    {
        return $this->hasMany(SharedCredential::class);
    }

    /** @return HasMany<AuditLog, $this> */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
