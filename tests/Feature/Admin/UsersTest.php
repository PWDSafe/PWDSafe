<?php

namespace Tests\Feature\Admin;

use App\AuditLog;
use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UsersTest extends TestCase
{
    use DatabaseMigrations;

    private function createAdminAndLogin(): User
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        $this->setupVaultSessionForUser($user, 'testing123');

        return $user->fresh();
    }

    public function testGuestIsRedirectedToLogin(): void
    {
        $this->get('/admin/users')->assertRedirect('/login');
    }

    public function testNonAdminCannotAccessUserList(): void
    {
        $user = $this->createAndLoginUser();
        $this->get('/admin/users')->assertForbidden();
    }

    public function testAdminCanViewUserList(): void
    {
        $this->createAdminAndLogin();
        User::factory()->create(['email' => 'other@example.com', 'name' => 'Other User']);

        $this->get('/admin/users')
            ->assertOk()
            ->assertSee('other@example.com')
            ->assertSee('Other User');
    }

    public function testUserListShowsLastLogin(): void
    {
        $admin = $this->createAdminAndLogin();
        $other = User::factory()->create();

        AuditLog::create([
            'user_id' => $other->id,
            'event' => 'login',
            'ip_address' => '127.0.0.1',
            'created_at' => now()->subHour(),
        ]);

        $this->get('/admin/users')->assertOk()->assertSee($other->email);
    }

    public function testUserListShowsNeverForUsersWithNoLoginHistory(): void
    {
        $this->createAdminAndLogin();
        $other = User::factory()->create(['email' => 'nologin@example.com']);

        $this->get('/admin/users')
            ->assertOk()
            ->assertSee('nologin@example.com')
            ->assertSee('"last_login_at":null');
    }

    public function testAdminCanResetUserPassword(): void
    {
        $this->createAdminAndLogin();
        $target = User::factory()->create([
            'email' => 'target@example.com',
            'uses_login_hash' => true,
            'login_salt' => str_repeat('a', 64),
            'vault_configured' => true,
            'separate_vault_password' => false,
        ]);

        $this->post("/admin/users/{$target->id}/reset-password", [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertRedirect(route('admin.users'));

        $target->refresh();
        $this->assertTrue(Hash::check('newpassword123', $target->password));
        $this->assertFalse((bool) $target->uses_login_hash);
        $this->assertNull($target->login_salt);
        $this->assertTrue((bool) $target->separate_vault_password);
    }

    public function testAdminResetPasswordOnPendingUserDoesNotSetSeparateVaultPassword(): void
    {
        $this->createAdminAndLogin();
        $target = User::factory()->create([
            'vault_configured' => false,
            'separate_vault_password' => false,
        ]);

        $this->post("/admin/users/{$target->id}/reset-password", [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertRedirect(route('admin.users'));

        $this->assertFalse((bool) $target->fresh()->separate_vault_password);
    }

    public function testPasswordResetRequiresMinimumLength(): void
    {
        $this->createAdminAndLogin();
        $target = User::factory()->create();

        $this->post("/admin/users/{$target->id}/reset-password", [
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');
    }

    public function testPasswordResetRequiresConfirmation(): void
    {
        $this->createAdminAndLogin();
        $target = User::factory()->create();

        $this->post("/admin/users/{$target->id}/reset-password", [
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword',
        ])->assertSessionHasErrors('password');
    }

    public function testNonAdminCannotResetPassword(): void
    {
        $this->createAndLoginUser();
        $target = User::factory()->create();

        $this->post("/admin/users/{$target->id}/reset-password", [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertForbidden();
    }

    public function testAdminCanUpdateUserName(): void
    {
        $this->createAdminAndLogin();
        $target = User::factory()->create(['email' => 'target@example.com', 'name' => null]);

        $this->patch("/admin/users/{$target->id}/name", ['name' => 'New Name'])
            ->assertRedirect(route('admin.users'))
            ->assertSessionHas('success');

        $this->assertSame('New Name', $target->fresh()->name);
    }

    public function testAdminCanClearUserName(): void
    {
        $this->createAdminAndLogin();
        $target = User::factory()->create(['name' => 'Old Name']);

        $this->patch("/admin/users/{$target->id}/name", ['name' => ''])
            ->assertRedirect(route('admin.users'));

        $this->assertNull($target->fresh()->name);
    }

    public function testNonAdminCannotUpdateName(): void
    {
        $this->createAndLoginUser();
        $target = User::factory()->create();

        $this->patch("/admin/users/{$target->id}/name", ['name' => 'Hacker'])->assertForbidden();
    }

    public function testAdminCanDeleteUser(): void
    {
        $this->createAdminAndLogin();
        $target = User::factory()->create(['email' => 'todelete@example.com']);

        $this->delete("/admin/users/{$target->id}")
            ->assertRedirect(route('admin.users'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function testAdminCannotDeleteOwnAccount(): void
    {
        $admin = $this->createAdminAndLogin();

        $this->delete("/admin/users/{$admin->id}")
            ->assertRedirect(route('admin.users'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function testNonAdminCannotDeleteUser(): void
    {
        $this->createAndLoginUser();
        $target = User::factory()->create();

        $this->delete("/admin/users/{$target->id}")->assertForbidden();
    }

    public function testAdminCanCreateUser(): void
    {
        $this->createAdminAndLogin();

        $this->post('/admin/users', [
            'email' => 'newuser@example.com',
            'name' => 'New User',
            'password' => 'temporarypass',
            'password_confirmation' => 'temporarypass',
        ])->assertRedirect(route('admin.users'))
            ->assertSessionHas('success');

        $user = User::where('email', 'newuser@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('New User', $user->name);
        $this->assertFalse((bool) $user->vault_configured);
        $this->assertSame('local', $user->auth_source);
        $this->assertFalse((bool) $user->uses_login_hash);
        $this->assertTrue(Hash::check('temporarypass', $user->password));
    }

    public function testAdminCanCreateUserWithoutName(): void
    {
        $this->createAdminAndLogin();

        $this->post('/admin/users', [
            'email' => 'noname@example.com',
            'password' => 'temporarypass',
            'password_confirmation' => 'temporarypass',
        ])->assertRedirect(route('admin.users'));

        $user = User::where('email', 'noname@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->name);
    }

    public function testCreateUserRequiresUniqueEmail(): void
    {
        $this->createAdminAndLogin();
        User::factory()->create(['email' => 'existing@example.com']);

        $this->post('/admin/users', [
            'email' => 'existing@example.com',
            'password' => 'temporarypass',
            'password_confirmation' => 'temporarypass',
        ])->assertSessionHasErrors('email');
    }

    public function testCreateUserRequiresPasswordConfirmation(): void
    {
        $this->createAdminAndLogin();

        $this->post('/admin/users', [
            'email' => 'newuser@example.com',
            'password' => 'temporarypass',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors('password');
    }

    public function testCreateUserRequiresMinimumPasswordLength(): void
    {
        $this->createAdminAndLogin();

        $this->post('/admin/users', [
            'email' => 'newuser@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');
    }

    public function testNonAdminCannotCreateUser(): void
    {
        $this->createAndLoginUser();

        $this->post('/admin/users', [
            'email' => 'newuser@example.com',
            'password' => 'temporarypass',
            'password_confirmation' => 'temporarypass',
        ])->assertForbidden();
    }
}
