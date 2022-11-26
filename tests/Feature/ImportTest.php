<?php
namespace Tests\Feature;

use App\Credential;
use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tests\TestCase;

class ImportTest extends TestCase {
    use DatabaseMigrations;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        User::registerUser('some@email.com', 'password');
        $this->user = User::first();
        Auth::loginUsingId($this->user->id);
        session()->put('password', 'password');
    }

    public function testImportingCredentials(): void
    {
        $filename = 'credentials_to_import.json';
        $path = base_path('tests/assets/') . $filename;
        $file = new UploadedFile(
            $path,
            $filename,
            'text/json',
            null,
            true
        );
        $this->from('/groups/' . $this->user->primarygroup)
            ->post('/import', [
                'jsonfile' => $file,
                'group' => $this->user->primarygroup,
            ])
            ->assertRedirect('/groups/' . $this->user->primarygroup)
            ->assertSessionHas('import_count', 2)
            ->assertSessionHas('import_skipped', 1);

        $this->assertCount(2, Credential::all());
    }

    public function testImportingMalformedCredentials(): void
    {
        $filename = 'credentials_malformed.json';
        $path = base_path('tests/assets/') . $filename;
        $file = new UploadedFile(
            $path,
            $filename,
            'text/json',
            null,
            true
        );
        $this->from('/groups/' . $this->user->primarygroup)
            ->post('/import', [
                'jsonfile' => $file,
                'group' => $this->user->primarygroup,
            ])
            ->assertRedirect('/groups/' . $this->user->primarygroup)
            ->assertSessionHasErrors('import_error');
    }

    public function testImportingNonJsonCredentials(): void
    {
        $filename = 'credentials_wrong_format.txt';
        $path = base_path('tests/assets/') . $filename;
        $file = new UploadedFile(
            $path,
            $filename,
            'text/plain',
            null,
            true
        );
        $this->from('/groups/' . $this->user->primarygroup)
            ->post('/import', [
                'jsonfile' => $file,
                'group' => $this->user->primarygroup,
            ])
            ->assertRedirect('/groups/' . $this->user->primarygroup)
            ->assertSessionHasErrors('import_error');
    }
}
