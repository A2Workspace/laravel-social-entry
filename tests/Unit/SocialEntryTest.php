<?php

namespace Tests\Unit;

use A2Workspace\SocialEntry\SocialEntry;
use A2Workspace\SocialEntry\RouteRegistrar;
use A2Workspace\SocialEntry\Proxy\SocialEntryProvider;
use A2Workspace\SocialEntry\Entity\AccessTokenRepository;
use A2Workspace\SocialEntry\Entity\AuthCodeRepository;
use A2Workspace\SocialEntry\Entity\IdentifierRepository;
use A2Workspace\SocialEntry\Exceptions\InvalidUserModelException;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Auth;
use Illuminate\Config\Repository as Config;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Contracts\Provider as ProviderContract;
use Mockery as m;
use Mockery\MockInterface;
use stdClass;

class SocialEntryTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();

        SocialEntry::useUserModel(null);
    }

    public function test_routes()
    {
        Container::setInstance($container = new Container);
        $container->instance(
            RouteRegistrar::class,
            m::mock(RouteRegistrar::class, function (MockInterface $mock) {
                $mock->shouldReceive('all');
            })
        );

        SocialEntry::routes();

        $this->assertTrue(true);
    }

    public function test_call_routes_method_with_callback()
    {
        Container::setInstance($container = new Container);
        $container->instance(
            RouteRegistrar::class,
            m::mock(RouteRegistrar::class, function (MockInterface $mock) {
                $mock->shouldReceive('all');
            })
        );

        $spy = m::mock(stdClass::class, function (MockInterface $mock) {
            $mock->shouldReceive('detected')->once();
        });

        SocialEntry::routes(function ($routeRegistrar) use ($spy) {
            \call_user_func([$spy, 'detected']);

            $this->assertInstanceOf(RouteRegistrar::class, $routeRegistrar);
        });
    }

    public function test_auth_code_repository_can_be_created()
    {
        $repository = SocialEntry::authCodes();

        $this->assertInstanceOf(AuthCodeRepository::class, $repository);
    }

    public function test_access_token_repository_can_be_created()
    {
        $repository = SocialEntry::accessTokens();

        $this->assertInstanceOf(AccessTokenRepository::class, $repository);
    }

    public function test_identifier_repository_can_be_created()
    {
        $repository = SocialEntry::identifiers();

        $this->assertInstanceOf(IdentifierRepository::class, $repository);
    }

    public function test_social_entry_provider_can_be_created()
    {
        Socialite::shouldReceive('driver')
            ->once()
            ->with('auth')
            ->andReturn(m::mock(ProviderContract::class));

        $provider = SocialEntry::provider('auth');

        $this->assertInstanceOf(SocialEntryProvider::class, $provider);
        $this->assertEquals('auth', $provider->getDriverName());
    }

    public function test_user_model_can_be_changed()
    {
        SocialEntry::useUserModel(\SocialEntryTest\UserStub::class);

        $this->assertEquals(\SocialEntryTest\UserStub::class, SocialEntry::userModel());
    }

    public function test_should_throw_exception_when_give_an_invalid_user_model()
    {
        $this->expectException(InvalidUserModelException::class);

        SocialEntry::useUserModel(\stdClass::class);
    }

    public function test_using_auth_user_model_in_defaulted()
    {
        Container::setInstance($container = new Container);

        $container->instance('config', $config = new Config);

        $config->set('auth.guards.auth_driver_name.provider', 'auth_provider_name');
        $config->set('auth.providers.auth_provider_name.model', stdClass::class);

        Auth::shouldReceive('getDefaultDriver')
            ->once()
            ->andReturn('auth_driver_name');

        $this->assertEquals(stdClass::class, SocialEntry::userModel());
    }

    public function test_should_throw_exception_when_auth_user_model_cannot_be_resolved()
    {
        $this->expectException(InvalidUserModelException::class);
        $this->expectExceptionMessage('Cannot resolve default user model');

        Container::setInstance($container = new Container);

        $container->instance('config', $config = new Config);

        $config->set('auth.guards.auth_driver_name.provider', 'auth_provider_name');
        $config->set('auth.providers.auth_provider_name.model', null);

        Auth::shouldReceive('getDefaultDriver')
            ->once()
            ->andReturn('auth_driver_name');

        SocialEntry::userModel();
    }

    public function test_should_be_using_custom_find_user_method()
    {
        SocialEntry::useUserModel(\SocialEntryTest\SpyUserStub::class);

        $this->assertEquals('FIND_FOR_SOCIALITE_WITH: social_id, auth', SocialEntry::findLocalUser('social_id', 'auth'));
    }
}

namespace SocialEntryTest;

class UserStub extends \Illuminate\Database\Eloquent\Model
{
    // ...
}

class SpyUserStub extends \Illuminate\Database\Eloquent\Model
{
    public function findForSocialite($identifier, $type)
    {
        return "FIND_FOR_SOCIALITE_WITH: {$identifier}, {$type}";
    }
}
