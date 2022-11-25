<?php

namespace Tests\Feature;

use App\Credential;
use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use DatabaseMigrations;

    public function testExportingWithoutCredentials(): void
    {
        User::registerUser('some@email.com', 'password');
        $user = User::first();
        Auth::loginUsingId($user->id);
        session()->put('password', 'password');

        $this->post(route('export', $user->primarygroup))
            ->assertDownload()
            ->assertContent(json_encode([]));
    }

    public function testExportingWithCredentials(): void
    {
        User::registerUser('some@email.com', 'password');
        $user = User::first();
        Auth::loginUsingId($user->id);
        session()->put('password', 'password');

        Credential::addCredentials([
            'creds' => 'a test site',
            'credu' => 'myusername',
            'credn' => '',
            'credp' => 'somePassword',
            'currentgroupid' => $user->primarygroup,
        ]);

        $this->post(route('export', $user->primarygroup))
            ->assertDownload()
            ->assertContent(json_encode(
                [
                    [
                        'site' => 'a test site',
                        'username' => 'myusername',
                        'password' => 'somePassword',
                        'notes' => ''
                    ]
                ]
            ));
    }
}
