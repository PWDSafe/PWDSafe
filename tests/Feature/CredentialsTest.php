<?php

namespace Tests\Feature;

use App\Credential;
use App\Group;
use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class CredentialsTest extends TestCase
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

    public function testAddingCredentials(): void
    {
        $this->get("/groups/{$this->user->primarygroup}/add")->assertSee('<add-credentials-form', false);
        $this->addTestCredential();
        $this->assertDatabaseHas('credentials', ['site' => 'Some site']);
        $credential = Credential::first();
        $this->assertDatabaseHas('encryptedcredentials', ['credentialid' => $credential->id, 'userid' => $this->user->id]);

        $this->get('/groups/' . $this->user->primarygroup)->assertSee('Some site');
    }

    public function testUpdatingCredentials(): void
    {
        $this->addTestCredential();
        $this->assertDatabaseHas('credentials', ['site' => 'Some site']);
        $credential = Credential::first();
        $this->assertDatabaseHas('encryptedcredentials', ['credentialid' => $credential->id, 'userid' => $this->user->id]);

        $currentPassword = $this->getPassword($credential);
        $this->put('/credential/' . $credential->id, [
            'creds' => 'New site',
            'credu' => $credential->username,
            'credn' => '',
            'currentgroupid' => $credential->groupid,
            'encrypted' => $this->encryptedPayloadForUsers($currentPassword, $this->user),
        ]);

        $this->assertDatabaseHas('credentials', ['site' => 'New site']);
        $this->assertCount(1, Credential::all());

        $newpassword = 'Some other password';

        $this->put('/credential/' . $credential->id, [
            'creds' => 'New site',
            'credu' => $credential->username,
            'credn' => '',
            'currentgroupid' => $credential->groupid,
            'encrypted' => $this->encryptedPayloadForUsers($newpassword, $this->user),
        ]);

        $this->assertEquals($newpassword, $this->getPassword($credential));

        $this->json('POST', '/groups/create', [
            'groupname' => 'testgroup',
        ]);

        $this->post('logout');
        $this->from('/login')->post('/login', ['email' => 'some@email.com', 'password' => 'password']);

        $group = Group::where('name', 'testgroup')->first();

        $this->json('PUT', '/credential/' . $credential->id, [
            'creds' => 'New site',
            'credu' => $credential->username,
            'credn' => '',
            'currentgroupid' => $group->id,
            'encrypted' => $this->encryptedPayloadForUsers($newpassword, $this->user),
        ])->assertOk();
        $credential = Credential::first();
        $this->assertEquals($group->id, $credential->groupid);
        $this->assertEquals('New site', $credential->site);
    }

    public function testRemovingCredentials(): void
    {
        $this->json('POST', "/groups/{$this->user->primarygroup}/add", [
            'site' => 'Some site',
            'user' => 'The username',
            'notes' => 'Notes',
            'encrypted' => $this->encryptedPayloadForUsers('The super secret password', $this->user),
        ]);

        $this->assertDatabaseHas('credentials', ['site' => 'Some site']);
        $credential = Credential::first();

        $this->get('/credential/' . $credential->id)->assertSee('Are you sure');

        $this->delete('/credential/' . $credential->id);
        $this->assertDatabaseMissing('credentials', ['site' => 'Some site']);
    }

    private function getPassword(Credential $credential): string
    {
        $response = json_decode($this->get('/pwdfor/' . $credential->id)->getContent(), true);
        $encryption = app(\App\Helpers\Encryption::class);

        return $encryption->decWithPriv($response['data'], $this->user->fresh()->decryptPrivkey());
    }

    public function testPasswordForReturnsEncryptedData(): void
    {
        $this->addTestCredential();
        $credential = Credential::first();

        $response = $this->getJson('/pwdfor/' . $credential->id)
            ->assertOk()
            ->assertJsonStructure(['status', 'data', 'user', 'site', 'notes', 'groupid'])
            ->assertJsonMissing(['pwd'])
            ->json();

        $encryption = app(\App\Helpers\Encryption::class);
        $decrypted = $encryption->decWithPriv($response['data'], $this->user->fresh()->decryptPrivkey());
        $this->assertEquals('The super secret password', $decrypted);
    }

    private function addTestCredential(): void
    {
        $this->post('/groups/' . $this->user->primarygroup . '/add', [
            'site' => 'Some site',
            'user' => 'The username',
            'notes' => 'Notes',
            'encrypted' => $this->encryptedPayloadForUsers('The super secret password', $this->user),
        ]);
    }
}
