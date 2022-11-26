<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use PragmaRX\Google2FAQRCode\Google2FA;
use Tests\TestCase;

class TwoFaTest extends TestCase
{
    use DatabaseMigrations;

    public function test2FaSettingsNotReachable(): void
    {
        $this->get(route('settings.twofa'))->assertStatus(302);
    }

    public function test2FaEnable(): void
    {
        $google2fa = new Google2FA();
        $user = $this->createAndLoginUser();
        $this->assertNull($user->two_factor_secret);
        $this->get('/settings/twofa')
            ->assertOk()
            ->assertSee('Two factor authentication')
            ->assertSee('Activate 2FA');
        $this->assertTrue(session()->has('2fa_secret'));
        $this->assertNotEmpty(session()->get('2fa_secret'));

        $this->post('/settings/twofa', [
            'currentpassword' => 'testing',
            'otpcode' => 'test'
        ])->assertSessionHasErrors('currentpassword');

        $this->post('/settings/twofa', [
            'currentpassword' => 'testing123',
            'otpcode' => 'test'
        ])->assertSessionHasErrors('otpcode');

        $code = $google2fa->getCurrentOtp(session()->get('2fa_secret'));

        $this->post('/settings/twofa', [
            'currentpassword' => 'testing123',
            'otpcode' => $code
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->get('/settings/twofa')->assertSee('Disable 2FA');
    }

    public function testLoginWith2FaEnabled(): void
    {
        $google2fa = new Google2FA();
        $user = User::factory()->create(['two_factor_secret' => encrypt($google2fa->generateSecretKey())]);
        $this->post('/login', ['email' => $user->email, 'password' => 'testing123'])
            ->assertRedirect('/verifyotp');

        $this->get('/verifyotp')
            ->assertOk()
            ->assertSee('Verify two factor authentication');

        $this->get('/securitycheck')->assertRedirect();

        $this->post('/verifyotp', ['twofacode' => 'test'])->assertSessionHasErrors('twofacode');
        $this->post('/verifyotp', ['twofacode' => $google2fa->getCurrentOtp(decrypt($user->two_factor_secret))])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->get('/securitycheck')->assertOk();
    }

    public function testDisable2Fa(): void
    {
        $google2fa = new Google2FA();
        $user = User::factory()->create(['two_factor_secret' => encrypt($google2fa->generateSecretKey())]);
        $this->actingAs($user);
        session()->put('password', 'testing123');

        $this->get('/settings/twofa')
            ->assertOk()
            ->assertSee('Two factor authentication')
            ->assertSee('Disable 2FA');

        $this->delete('/settings/twofa', [
            'currentpassword' => 'wrongpassword',
            'otpcode' => $google2fa->getCurrentOtp(decrypt($user->two_factor_secret))
        ])
            ->assertSessionHasErrors('currentpassword');

        $this->delete('/settings/twofa', [
            'currentpassword' => 'testing123',
            'otpcode' => '112233'
        ])
            ->assertSessionHasErrors('otpcode');

        $this->delete('/settings/twofa', [
            'currentpassword' => 'testing123',
            'otpcode' => $google2fa->getCurrentOtp(decrypt($user->two_factor_secret))
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->get('/settings/twofa')->assertSee('Activate 2FA');
    }
}
