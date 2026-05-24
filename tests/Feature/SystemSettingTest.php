<?php

namespace Tests\Feature;

use App\SystemSetting;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class SystemSettingTest extends TestCase
{
    use DatabaseMigrations;

    public function testGetReturnsNullWhenKeyNotSet(): void
    {
        $this->assertNull(SystemSetting::get('nonexistent'));
    }

    public function testGetReturnsDefaultWhenKeyNotSet(): void
    {
        $this->assertEquals('fallback', SystemSetting::get('nonexistent', 'fallback'));
    }

    public function testSetAndGetRoundTrip(): void
    {
        SystemSetting::set('auth_method', 'ldap');

        $this->assertEquals('ldap', SystemSetting::get('auth_method'));
    }

    public function testSetUpdatesExistingValue(): void
    {
        SystemSetting::set('auth_method', 'internal');
        SystemSetting::set('auth_method', 'ldap');

        $this->assertEquals('ldap', SystemSetting::get('auth_method'));
        $this->assertDatabaseCount('system_settings', 1);
    }

    public function testSetCreatesNewRecord(): void
    {
        $this->assertDatabaseCount('system_settings', 0);

        SystemSetting::set('auth_method', 'internal');

        $this->assertDatabaseCount('system_settings', 1);
        $this->assertDatabaseHas('system_settings', ['key' => 'auth_method', 'value' => 'internal']);
    }
}
