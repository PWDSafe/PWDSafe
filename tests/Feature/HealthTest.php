<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class HealthTest extends TestCase
{
    use DatabaseMigrations;

    public function testHealth(): void
    {
        $this->get('/health')->assertOk();
    }
}
