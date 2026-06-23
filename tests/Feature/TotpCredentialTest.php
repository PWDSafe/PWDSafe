<?php

namespace Tests\Feature;

use App\Credential;
use App\Encryptedcredential;
use App\Group;
use App\Helpers\Encryption;
use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class TotpCredentialTest extends TestCase
{
    use DatabaseMigrations;

    private User $user;

    public function setUp(): void
    {
        parent::setUp();
        User::registerUser('some@email.com', 'password');
        $this->user = User::first();
        $this->actingAs($this->user);
        $this->setupVaultSessionForUser($this->user, 'password');
    }

    public function testStoringCredentialWithTotpSecretSetsHasTotp(): void
    {
        $this->postCredentialWithTotp('JBSWY3DPEHPK3PXP');

        $this->assertDatabaseHas('credentials', ['name' => 'Test site', 'has_totp' => true]);
        $credential = Credential::first();
        $encryptedRow = Encryptedcredential::where('credentialid', $credential->id)
            ->where('userid', $this->user->id)
            ->first();

        $this->assertNotNull($encryptedRow->totp_secret, 'TOTP secret should be stored encrypted');
    }

    public function testStoringCredentialWithoutTotpSecretHasHasTotpFalse(): void
    {
        $this->post('/groups/' . $this->user->primarygroup . '/add', [
            'name' => 'Test site',
            'user' => 'theuser',
            'notes' => '',
            'encrypted' => $this->encryptedPayloadForUsers('password123', $this->user),
        ]);

        $this->assertDatabaseHas('credentials', ['name' => 'Test site', 'has_totp' => false]);
        $credential = Credential::first();
        $encryptedRow = Encryptedcredential::where('credentialid', $credential->id)
            ->where('userid', $this->user->id)
            ->first();

        $this->assertNull($encryptedRow->totp_secret);
    }

    public function testPasswordForEndpointReturnsTotpData(): void
    {
        $this->postCredentialWithTotp('JBSWY3DPEHPK3PXP');
        $credential = Credential::first();

        $this->getJson('/pwdfor/' . $credential->id)
            ->assertOk()
            ->assertJsonFragment(['has_totp' => true])
            ->assertJsonStructure(['status', 'data', 'totp_secret', 'has_totp', 'user', 'name', 'url', 'notes', 'groupid']);
    }

    public function testPasswordForEndpointReturnsTotpSecretEncrypted(): void
    {
        $totpPlaintext = 'JBSWY3DPEHPK3PXP';
        $this->postCredentialWithTotp($totpPlaintext);
        $credential = Credential::first();

        $response = $this->getJson('/pwdfor/' . $credential->id)->json();

        $this->assertNotEquals($totpPlaintext, $response['totp_secret'], 'TOTP secret should be encrypted in response');
        $encryption = app(Encryption::class);
        $decrypted = $encryption->decWithPriv($response['totp_secret'], $this->user->fresh()->decryptPrivkey());
        $this->assertEquals($totpPlaintext, $decrypted);
    }

    public function testPasswordForEndpointReturnsNullTotpSecretWhenNotSet(): void
    {
        $this->post('/groups/' . $this->user->primarygroup . '/add', [
            'name' => 'Test site',
            'user' => 'theuser',
            'notes' => '',
            'encrypted' => $this->encryptedPayloadForUsers('password123', $this->user),
        ]);
        $credential = Credential::first();

        $this->getJson('/pwdfor/' . $credential->id)
            ->assertOk()
            ->assertJsonFragment(['has_totp' => false])
            ->assertJsonFragment(['totp_secret' => null]);
    }

    public function testUpdatingCredentialReplacesTotpSecret(): void
    {
        $this->postCredentialWithTotp('JBSWY3DPEHPK3PXP');
        $credential = Credential::first();
        $encryption = app(Encryption::class);
        $privkey = $this->user->fresh()->decryptPrivkey();

        $currentPassword = $this->getDecryptedPassword($credential);
        $newTotpSecret = 'NEWBASE32SECRET2';

        $this->put('/credential/' . $credential->id, [
            'creds' => $credential->name,
            'credu' => $credential->username,
            'credn' => $credential->notes,
            'currentgroupid' => $credential->groupid,
            'has_totp' => true,
            'encrypted' => $this->encryptedPayloadForUsersWithTotp($currentPassword, $newTotpSecret, $this->user),
        ]);

        $response = $this->getJson('/pwdfor/' . $credential->id)->json();
        $decryptedTotp = $encryption->decWithPriv($response['totp_secret'], $privkey);
        $this->assertEquals($newTotpSecret, $decryptedTotp);
    }

    public function testUpdatingCredentialRemovesTotpSecret(): void
    {
        $this->postCredentialWithTotp('JBSWY3DPEHPK3PXP');
        $credential = Credential::first();
        $currentPassword = $this->getDecryptedPassword($credential);

        $this->put('/credential/' . $credential->id, [
            'creds' => $credential->name,
            'credu' => $credential->username,
            'credn' => $credential->notes,
            'currentgroupid' => $credential->groupid,
            'has_totp' => false,
            'encrypted' => $this->encryptedPayloadForUsers($currentPassword, $this->user),
        ]);

        $this->assertDatabaseHas('credentials', ['id' => $credential->id, 'has_totp' => false]);
        $encryptedRow = Encryptedcredential::where('credentialid', $credential->id)
            ->where('userid', $this->user->id)
            ->first();
        $this->assertNull($encryptedRow->totp_secret);
    }

    public function testMovingCredentialToAnotherGroupPreservesTotpSecret(): void
    {
        $totpPlaintext = 'JBSWY3DPEHPK3PXP';
        $this->postCredentialWithTotp($totpPlaintext);
        $credential = Credential::first();

        $destinationGroup = Group::factory()->create();
        $this->user->groups()->attach($destinationGroup, ['permission' => 'admin']);

        $currentPassword = $this->getDecryptedPassword($credential);
        $this->user->unsetRelation('groups');

        $this->put('/credential/' . $credential->id, [
            'creds' => $credential->name,
            'credu' => $credential->username,
            'credn' => $credential->notes,
            'currentgroupid' => $destinationGroup->id,
            'has_totp' => true,
            'encrypted' => $this->encryptedPayloadForUsersWithTotp($currentPassword, $totpPlaintext, $this->user),
        ])->assertStatus(302)->assertSessionHasNoErrors();

        $movedCredential = Credential::where('groupid', $destinationGroup->id)->first();
        $this->assertNotNull($movedCredential);
        $this->assertTrue($movedCredential->has_totp);

        $response = $this->getJson('/pwdfor/' . $movedCredential->id)->json();
        $encryption = app(Encryption::class);
        $decryptedTotp = $encryption->decWithPriv($response['totp_secret'], $this->user->fresh()->decryptPrivkey());
        $this->assertEquals($totpPlaintext, $decryptedTotp);
    }

    public function testUnauthorizedUserCannotViewTotpData(): void
    {
        $this->postCredentialWithTotp('JBSWY3DPEHPK3PXP');
        $credential = Credential::first();

        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        $this->getJson('/pwdfor/' . $credential->id)->assertForbidden();
    }

    private function postCredentialWithTotp(string $totpSecret): void
    {
        $this->post('/groups/' . $this->user->primarygroup . '/add', [
            'name' => 'Test site',
            'user' => 'theuser',
            'notes' => '',
            'has_totp' => true,
            'encrypted' => $this->encryptedPayloadForUsersWithTotp('password123', $totpSecret, $this->user),
        ]);
    }

    private function getDecryptedPassword(Credential $credential): string
    {
        $response = $this->getJson('/pwdfor/' . $credential->id)->json();
        $encryption = app(Encryption::class);

        return $encryption->decWithPriv($response['data'], $this->user->fresh()->decryptPrivkey());
    }

    /**
     * @return array<int, array{userid: int, data: string, totp_secret: string}>
     */
    private function encryptedPayloadForUsersWithTotp(string $password, string $totpSecret, User ...$users): array
    {
        $encryption = app(Encryption::class);

        return array_values(array_map(
            fn (User $u) => [
                'userid' => $u->id,
                'data' => $encryption->encWithPub($password, $u->pubkey),
                'totp_secret' => $encryption->encWithPub($totpSecret, $u->pubkey),
            ],
            $users
        ));
    }
}
