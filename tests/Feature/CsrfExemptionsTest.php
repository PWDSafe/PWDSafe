<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use ReflectionMethod;
use Tests\TestCase;

class CsrfExemptionsTest extends TestCase
{
    private function inExceptArray(Request $request): bool
    {
        $middleware = app(PreventRequestForgery::class);

        $method = new ReflectionMethod($middleware, 'inExceptArray');
        $method->setAccessible(true);

        return $method->invoke($middleware, $request);
    }

    public function testCreateGroupEndpointIsExemptFromCsrf(): void
    {
        $request = Request::create('/api/groups', 'POST');

        $this->assertTrue($this->inExceptArray($request));
    }

    public function testCreateCredentialEndpointIsExemptFromCsrf(): void
    {
        $request = Request::create('/api/groups/42/credentials', 'POST');

        $this->assertTrue($this->inExceptArray($request));
    }

    public function testMoveCredentialEndpointIsExemptFromCsrf(): void
    {
        $request = Request::create('/api/credentials/42/move', 'POST');

        $this->assertTrue($this->inExceptArray($request));
    }

    public function testUnrelatedGroupEndpointsAreNotExempt(): void
    {
        $request = Request::create('/api/groups/42/members/prepare', 'POST');

        $this->assertFalse($this->inExceptArray($request));
    }
}
