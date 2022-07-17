<?php

namespace Tests\Unit\Proxy;

use A2Workspace\SocialEntry\Proxy\SocialUser;
use A2Workspace\SocialEntry\Proxy\SocialEntryProvider;
use A2Workspace\SocialEntry\Exceptions\MissingConfigureException;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Config\Repository as Config;
use Laravel\Socialite\SocialiteManager;
use Laravel\Socialite\Contracts\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Contracts\Provider as ProviderContract;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Mockery as m;
use Mockery\MockInterface;
use Tests\Unit\TestCase;
use ErrorException;

class SocialEntryProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        Socialite::clearResolvedInstances();

        m::close();
    }

    public function test_provider_name_resolves()
    {
        $proxy = new SocialEntryProvider(new \SocialEntryProviderTest\ForNameResolvingProvider);

        $this->assertEquals('fornameresolving', $proxy->getDriverName());
    }

    public function test_wrap_socialite_provider_instance()
    {
        $mockedProvider = m::mock(
            ProviderContract::class,
            function (MockInterface $mock) {
                $mock->shouldReceive('redirect')
                    ->once()
                    ->andReturn(
                        new RedirectResponse('http://localhost/login/oauth/authorize')
                    );

                $mock->shouldReceive('user')
                    ->once()
                    ->andReturn(m::mock(User::class));
            }
        );

        /** @var \Laravel\Socialite\Contracts\Provider $mockedProvider */
        $proxy = new SocialEntryProvider($mockedProvider, 'auth');

        $this->assertInstanceOf(SocialEntryProvider::class, $proxy);
        $this->assertEquals('auth', $proxy->getDriverName());

        $response = $proxy->redirect();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('http://localhost/login/oauth/authorize', $response->getTargetUrl());

        $user = $proxy->user();
        $this->assertInstanceOf(SocialUser::class, $user);
    }

    public function test_provider_for()
    {
        $mockedProvider = m::mock(
            ProviderContract::class,
            function (MockInterface $mock) {
                $mock->shouldReceive('redirect')
                    ->once()
                    ->andReturn(
                        new RedirectResponse('http://localhost/login/oauth/authorize')
                    );

                $mock->shouldReceive('user')
                    ->once()
                    ->andReturn(m::mock(User::class));
            }
        );

        Socialite::shouldReceive('driver')
            ->once()
            ->with('auth')
            ->andReturn($mockedProvider);

        $proxy = SocialEntryProvider::providerFor('auth');

        $this->assertInstanceOf(SocialEntryProvider::class, $proxy);
        $this->assertEquals('auth', $proxy->getDriverName());

        $response = $proxy->redirect();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('http://localhost/login/oauth/authorize', $response->getTargetUrl());

        $user = $proxy->user();
        $this->assertInstanceOf(SocialUser::class, $user);
    }

    public function test_should_throw_exception_when_service_configure_missing()
    {
        $container = new Container;
        $container->instance('config', new Config);
        $container->instance('request', m::mock(Request::class));

        Socialite::setFacadeApplication($container);
        $container->instance(SocialiteFactory::class, new SocialiteManager($container));

        $this->expectException(MissingConfigureException::class);
        $this->expectExceptionMessage('Service [github] not configured');

        SocialEntryProvider::providerFor('github');
    }

    public function test_without_catch_other_errors_in_build_provider()
    {
        $this->expectException(ErrorException::class);

        Socialite::shouldReceive('driver')
            ->once()
            ->andReturnUsing(function () {
                throw new ErrorException;
            });

        SocialEntryProvider::providerFor('auth');
    }
}

namespace SocialEntryProviderTest;

class ForNameResolvingProvider implements \Laravel\Socialite\Contracts\Provider
{
    public function redirect()
    {
        return null;
    }

    public function user()
    {
        return null;
    }
}
