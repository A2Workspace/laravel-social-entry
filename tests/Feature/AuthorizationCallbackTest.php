<?php

namespace Tests\Feature;

use A2Workspace\SocialEntry\Exceptions\InvalidStateException;
use A2Workspace\SocialEntry\Exceptions\MissingConfigureException;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Config;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Mockery as m;

class AuthorizationCallbackTest extends TestCase
{
    use DatabaseMigrations;

    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }

    public function test_socialite_redirect_back_and_complete_access_with_code()
    {
        $socialiteManager = Socialite::getFacadeRoot();
        $socialiteManager->extend('auth', function () use ($socialiteManager) {
            return $socialiteManager->buildProvider(
                \AuthorizationCallbackTest\ProviderStub::class,
                [
                    'client_id' => 'client_id',
                    'client_secret' => 'client_secret',
                    'redirect' => 'redirect',
                ]
            );
        });

        $this->withSession([
            'entry_continue' => 'http://example.com/login',
            'entry_provider' => 'auth',
            'entry_scopes' => null,
        ]);

        $response = $this->call(
            'GET',
            '/auth/socialite/auth/callback',
            ['code' => 'auth_code']
        );

        $response->assertRedirectContains('http://example.com/login?code=');
        $response->assertSessionMissing('entry_continue');
        $response->assertSessionMissing('entry_provider');
        $response->assertSessionMissing('entry_scopes');

        $this->assertDatabaseHas('social_entry_auth_codes', [
            'identifier' => 'social_id',
            'provider' => 'auth',
            'revoked' => false,
        ]);
    }

    public function test_incorrect_entry_point()
    {
        $socialiteManager = Socialite::getFacadeRoot();
        $socialiteManager->extend('auth', function () use ($socialiteManager) {
            return $socialiteManager->buildProvider(
                \AuthorizationCallbackTest\ProviderStub::class,
                [
                    'client_id' => 'client_id',
                    'client_secret' => 'client_secret',
                    'redirect' => 'redirect',
                ]
            );
        });

        $response = $this->call(
            'GET',
            '/auth/socialite/auth/callback',
            ['code' => 'auth_code']
        );

        $response->assertStatus(419);

        $this->assertInstanceOf(InvalidStateException::class, $response->exception);
        $this->assertEquals(419, $response->exception->getStatusCode());
        $this->assertEquals('Invalid state', $response->exception->getMessage());
    }

    public function test_should_throw_exception_when_socialite_requesting_failed()
    {
        $socialiteManager = Socialite::getFacadeRoot();
        $socialiteManager->extend('auth', function () use ($socialiteManager) {
            return $socialiteManager->buildProvider(
                \AuthorizationCallbackTest\BadResponseProviderStub::class,
                [
                    'client_id' => 'client_id',
                    'client_secret' => 'client_secret',
                    'redirect' => 'redirect',
                ]
            );
        });

        $this->withSession([
            'entry_continue' => 'http://example.com/login',
            'entry_provider' => 'auth',
            'entry_scopes' => null,
        ]);

        $response = $this->call(
            'GET',
            '/auth/socialite/auth/callback',
            ['code' => 'auth_code']
        );

        $response->assertStatus(419);

        $this->assertInstanceOf(InvalidStateException::class, $response->exception);
        $this->assertEquals(419, $response->exception->getStatusCode());
        $this->assertEquals('Invalid state', $response->exception->getMessage());
    }

    public function test_should_throw_exception_when_social_user_not_return()
    {
        $socialiteManager = Socialite::getFacadeRoot();
        $socialiteManager->extend('auth', function () use ($socialiteManager) {
            return $socialiteManager->buildProvider(
                \AuthorizationCallbackTest\InvalidStateProviderStub::class,
                [
                    'client_id' => 'client_id',
                    'client_secret' => 'client_secret',
                    'redirect' => 'redirect',
                ]
            );
        });

        $this->withSession([
            'entry_continue' => 'http://example.com/login',
            'entry_provider' => 'auth',
            'entry_scopes' => null,
        ]);

        $response = $this->call(
            'GET',
            '/auth/socialite/auth/callback',
            ['code' => 'auth_code']
        );

        $response->assertStatus(419);

        $this->assertInstanceOf(InvalidStateException::class, $response->exception);
        $this->assertEquals(419, $response->exception->getStatusCode());
        $this->assertEquals('Invalid state', $response->exception->getMessage());
    }

    public function test_should_throw_exception_when_service_not_configured()
    {
        Config::set('social-entry.providers', ['github']);

        $this->withSession([
            'entry_continue' => 'http://example.com/login',
            'entry_provider' => 'github',
            'entry_scopes' => null,
        ]);

        $response = $this->call(
            'GET',
            '/auth/socialite/github/callback',
            ['code' => 'auth_code']
        );

        $response->assertStatus(500);
        $this->assertInstanceOf(MissingConfigureException::class, $response->exception);
        $this->assertEquals('Service [github] not configured', $response->exception->getMessage());
    }
}

namespace AuthorizationCallbackTest;

use Mockery as m;
use Mockery\MockInterface;

class ProviderStub extends \Laravel\Socialite\Two\AbstractProvider
{
    protected function getAuthUrl($state)
    {
        return 'http://auth.com/login/oauth/authorize';
    }

    protected function getTokenUrl()
    {
        return 'http://auth.com/login/oauth/access_token';
    }

    protected function hasInvalidState()
    {
        return false;
    }

    public function getAccessTokenResponse($code)
    {
        return [
            'access_token' => 'oauth_access_token',
            'refresh_token' => 'oauth_refresh_token',
            'expires_in' => 'oauth_expires_in',
            'scope' => 'oauth_scope',
        ];
    }

    protected function getUserByToken($token)
    {
        return [];
    }

    protected function mapUserToObject(array $user)
    {
        return new UserStub;
    }
}

class UserStub implements \Laravel\Socialite\Contracts\User
{
    public function getId()
    {
        return 'social_id';
    }

    public function getEmail()
    {
        return 'social_email';
    }

    public function getNickname()
    {
        return 'social_nickname';
    }

    public function getName()
    {
        return 'social_username';
    }

    public function getAvatar()
    {
        return 'social_avatar';
    }

    public function setToken()
    {
        return $this;
    }

    public function setRefreshToken()
    {
        return $this;
    }

    public function setExpiresIn()
    {
        return $this;
    }

    public function setApprovedScopes()
    {
        return $this;
    }
}

class InvalidStateProviderStub extends ProviderStub
{
    public function user()
    {
        return null;
    }
}

class BadResponseProviderStub extends ProviderStub
{
    public function getAccessTokenResponse($code)
    {
        /** @var \Psr\Http\Message\RequestInterface $request  */
        $request = m::mock(\Psr\Http\Message\RequestInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('getStatusCode')->andReturn(400);
        });

        /** @var \Psr\Http\Message\ResponseInterface $response  */
        $response = m::mock(\Psr\Http\Message\ResponseInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('getStatusCode')->andReturn(400);
        });

        throw new \GuzzleHttp\Exception\ClientException('message', $request, $response);
    }
}
