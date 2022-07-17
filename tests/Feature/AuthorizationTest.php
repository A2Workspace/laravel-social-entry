<?php

namespace Tests\Feature;

use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Contracts\Provider as ProviderContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Config;
use Mockery as m;
use Mockery\MockInterface;

class AuthorizationTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }

    public function test_redirect_generates()
    {
        $mockedProvider = m::mock(
            ProviderContract::class,
            function (MockInterface $mock) {
                $mock->shouldReceive('redirect')
                    ->once()
                    ->andReturn(
                        new RedirectResponse('http://localhost/login/oauth/authorize')
                    );
            }
        );

        Socialite::shouldReceive('driver')
            ->with('auth')
            ->andReturn($mockedProvider);

        Config::set('social-entry.providers', ['auth']);

        $response = $this->call(
            'GET',
            '/auth/socialite',
            [
                'continue' => 'http://example.com/login',
                'provider' => 'auth',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('entry_continue', 'http://example.com/login');
        $response->assertSessionHas('entry_provider', 'auth');
        $this->assertSame('http://localhost/login/oauth/authorize', $response->getTargetUrl());
    }

    public function test_missing_continue_parameter()
    {
        Config::set('social-entry.providers', ['auth']);

        $response = $this->call(
            'GET',
            '/auth/socialite',
            [
                'provider' => 'auth',
            ]
        );

        $response->assertInvalid('continue');
        $response->assertValid('provider');

        $response->assertSessionMissing('entry_continue');
        $response->assertSessionMissing('entry_provider');
    }

    public function test_invalid_provider()
    {
        $response = $this->call(
            'GET',
            '/auth/socialite',
            [
                'continue' => 'http://example.com/login',
                'provider' => 'invalid',
            ]
        );

        $response->assertValid('continue');
        $response->assertInvalid('provider');

        $response->assertSessionMissing('entry_continue');
        $response->assertSessionMissing('entry_provider');
    }
}
