<?php

namespace Tests\Feature;

use App\Credential;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class CredentialsTest extends TestCase
{
    use DatabaseMigrations;

    private \App\User $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->post('/register', [
            'email' => 'some@email.com',
            'password' => 'password',
            'password_confirmation' => 'password'
        ]);
        $this->post('logout');
        $this->from('/login')->post('/login', ['email' => 'some@email.com', 'password' => 'password']);
        $this->user = \App\User::first();
    }

    public function testAddingCredentials(): void
    {
        $this->get("/groups/{$this->user->primarygroup}/add")->assertSee('Add credential');
        $this->addTestCredential();
        $this->assertDatabaseHas('credentials', ['site' => 'Some site']);
        $credential = \App\Credential::first();
        $this->assertDatabaseHas('encryptedcredentials', ['credentialid' => $credential->id, 'userid' => $this->user->id]);

        $this->get('/groups/' . $this->user->primarygroup)->assertSee('Some site');
    }

    public function testUpdatingCredentials(): void
    {
        $this->addTestCredential();
        $this->assertDatabaseHas('credentials', ['site' => 'Some site']);
        $credential = \App\Credential::first();
        $this->assertDatabaseHas('encryptedcredentials', ['credentialid' => $credential->id, 'userid' => $this->user->id]);

        $this->put('/credential/' . $credential->id, [
            'creds' => 'New site',
            'credu' => $credential->username,
            'credp' => $this->getPassword($credential),
            'credn' => '',
            'currentgroupid' => $credential->groupid,
        ]);

        $this->assertDatabaseHas('credentials', ['site' => 'New site']);
        $this->assertCount(1, \App\Credential::all());

        $newpassword = 'Some other password';

        $this->put('/credential/' . $credential->id, [
            'creds' => 'New site',
            'credu' => $credential->username,
            'credp' => $newpassword,
            'credn' => '',
            'currentgroupid' => $credential->groupid,
        ]);

        $this->assertEquals($newpassword, $this->getPassword($credential));

        $this->json('POST', '/groups/create', [
            'groupname' => 'testgroup',
        ]);

        $group = \App\Group::where('name', 'testgroup')->first();

        $this->json('PUT', '/credential/' . $credential->id, [
            'creds' => 'New site',
            'credu' => $credential->username,
            'credp' => $newpassword,
            'credn' => '',
            'currentgroupid' => $group->id,
        ]);
        $credential = \App\Credential::first();
        $this->assertEquals($group->id, $credential->groupid);
        $this->assertEquals('New site', $credential->site);
    }

    public function testRemovingCredentials(): void
    {
        $this->json('POST', "/groups/{$this->user->primarygroup}/add", [
            'site' => 'Some site',
            'user' => 'The username',
            'pass' => 'The super secret password',
            'notes' => 'Notes'
        ]);

        $this->assertDatabaseHas('credentials', ['site' => 'Some site']);
        $credential = \App\Credential::first();

        $this->get('/credential/' . $credential->id)->assertSee('Are you sure');

        $this->delete('/credential/' . $credential->id);
        $this->assertDatabaseMissing('credentials', ['site' => 'Some site']);
    }

    public function testImportingCredentials(): void
    {
        $filename = 'credentials_to_import.csv';
        $path = base_path('tests/assets/') . $filename;
        $file = new \Symfony\Component\HttpFoundation\File\UploadedFile ($path, $filename, 'text/csv', null, true);
        $res = $this->post('/import', [
            'csvfile' => $file,
            'group' => $this->user->primarygroup,
        ]);

        $this->assertCount(2, \App\Credential::all());
    }

    private function getPassword(Credential $credential): mixed
    {
        return json_decode($this->get('/pwdfor/' . $credential->id)->getContent(), true)['pwd'];
    }

    private function addTestCredential(): void
    {
        $this->post('/groups/' . $this->user->primarygroup . '/add', [
            'site' => 'Some site',
            'user' => 'The username',
            'pass' => 'The super secret password',
            'notes' => 'Notes',
        ]);
    }
}
