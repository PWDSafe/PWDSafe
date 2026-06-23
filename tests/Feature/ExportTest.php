<?php

namespace Tests\Feature;

use App\Credential;
use App\Helpers\Encryption;
use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use DatabaseMigrations;

    public function testExportDataApiWithNoCredentials(): void
    {
        User::registerUser('some@email.com', 'password');
        $user = User::first();
        Auth::loginUsingId($user->id);
        $this->setupVaultSessionForUser($user, 'password');

        $this->getJson("/api/groups/{$user->primarygroup}/export-data")
            ->assertOk()
            ->assertExactJson([]);
    }

    public function testExportDataApiWithCredentials(): void
    {
        User::registerUser('some@email.com', 'password');
        $user = User::first();
        Auth::loginUsingId($user->id);
        $this->setupVaultSessionForUser($user, 'password');

        $encryption = app(Encryption::class);

        Credential::addCredentials([
            'creds' => 'a test site',
            'credu' => 'myusername',
            'credn' => '',
            'encrypted' => $this->encryptedPayloadForUsers('somePassword', $user),
            'currentgroupid' => $user->primarygroup,
        ]);

        $response = $this->getJson("/api/groups/{$user->primarygroup}/export-data")
            ->assertOk()
            ->json();

        $this->assertCount(1, $response);
        $this->assertEquals('a test site', $response[0]['name']);
        $this->assertEquals('myusername', $response[0]['username']);

        $decrypted = $encryption->decWithPriv($response[0]['data'], $user->fresh()->decryptPrivkey());
        $this->assertEquals('somePassword', $decrypted);
    }

    public function testExportDataApiIncludesTotpSecret(): void
    {
        User::registerUser('some@email.com', 'password');
        $user = User::first();
        Auth::loginUsingId($user->id);
        $this->setupVaultSessionForUser($user, 'password');

        $encryption = app(Encryption::class);
        $totpPlaintext = 'JBSWY3DPEHPK3PXP';

        Credential::addCredentials([
            'creds' => 'a test site',
            'credu' => 'myusername',
            'credn' => '',
            'has_totp' => true,
            'encrypted' => [
                [
                    'userid' => $user->id,
                    'data' => $encryption->encWithPub('somePassword', $user->pubkey),
                    'totp_secret' => $encryption->encWithPub($totpPlaintext, $user->pubkey),
                ],
            ],
            'currentgroupid' => $user->primarygroup,
        ]);

        $response = $this->getJson("/api/groups/{$user->primarygroup}/export-data")
            ->assertOk()
            ->json();

        $this->assertTrue($response[0]['has_totp']);
        $this->assertNotNull($response[0]['totp_secret']);

        $decrypted = $encryption->decWithPriv($response[0]['totp_secret'], $user->fresh()->decryptPrivkey());
        $this->assertEquals($totpPlaintext, $decrypted);
    }

    public function testExportDataApiReturnsNullTotpSecretWhenNotSet(): void
    {
        User::registerUser('some@email.com', 'password');
        $user = User::first();
        Auth::loginUsingId($user->id);
        $this->setupVaultSessionForUser($user, 'password');

        $encryption = app(Encryption::class);

        Credential::addCredentials([
            'creds' => 'a test site',
            'credu' => 'myusername',
            'credn' => '',
            'encrypted' => $this->encryptedPayloadForUsers('somePassword', $user),
            'currentgroupid' => $user->primarygroup,
        ]);

        $response = $this->getJson("/api/groups/{$user->primarygroup}/export-data")
            ->assertOk()
            ->json();

        $this->assertFalse($response[0]['has_totp']);
        $this->assertNull($response[0]['totp_secret']);
    }

    public function testExportDataApiRequiresGroupMembership(): void
    {
        User::registerUser('some@email.com', 'password');
        $user1 = User::first();
        Auth::loginUsingId($user1->id);
        $this->setupVaultSessionForUser($user1, 'password');

        User::registerUser('other@email.com', 'password');
        $user2 = User::where('email', 'other@email.com')->first();

        $this->getJson("/api/groups/{$user2->primarygroup}/export-data")
            ->assertStatus(403);
    }
}
