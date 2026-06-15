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
