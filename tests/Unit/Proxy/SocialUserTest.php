<?php

namespace Tests\Unit\Proxy;

use A2Workspace\SocialEntry\Proxy\SocialUser;
use A2Workspace\SocialEntry\Proxy\SocialEntryProvider;
use Laravel\Socialite\Contracts\User as UserContract;
use Mockery as m;
use Mockery\MockInterface;
use Tests\Unit\TestCase;

class SocialUserTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }

    public function test_create_a_new_social_user()
    {
        $mockedUser = m::mock(UserContract::class, function (MockInterface $mock) {
            $mock->shouldReceive('getId')->andReturn('social_id');
            $mock->shouldReceive('getNickname')->andReturn('social_nickname');
            $mock->shouldReceive('getName')->andReturn('social_username');
            $mock->shouldReceive('getEmail')->andReturn('social_email');
            $mock->shouldReceive('getAvatar')->andReturn('social_avatar');
        });

        $mockedProvider = m::mock(SocialEntryProvider::class, function (MockInterface $mock) {
            $mock->shouldReceive('getDriverName')->andReturn('auth');
        });

        /** @var \Laravel\Socialite\Contracts\User $mockedUser */
        /** @var \A2Workspace\SocialEntry\Proxy\SocialEntryProvider $mockedProvider */
        $user = new SocialUser($mockedUser, $mockedProvider);

        $this->assertEquals('social_id', $user->getId());
        $this->assertEquals('social_nickname', $user->getNickname());
        $this->assertEquals('social_username', $user->getName());
        $this->assertEquals('social_email', $user->getEmail());
        $this->assertEquals('social_avatar', $user->getAvatar());

        $this->assertInstanceOf(SocialEntryProvider::class, $user->getProvider());
        $this->assertEquals('auth', $user->getProviderName());
    }

    public function test_handling_special_avatar_url()
    {
        $user = m::mock(UserContract::class, function (MockInterface $mock) {
            $mock->shouldReceive('getAvatar')
                ->andReturn('http://example.com/media/000000?type=normal');
        });

        $provider = m::mock(SocialEntryProvider::class, function (MockInterface $mock) {
            $mock->shouldReceive('getDriverName')->andReturn('facebook');
        });

        /** @var \Laravel\Socialite\Contracts\User $user */
        /** @var \A2Workspace\SocialEntry\Proxy\SocialEntryProvider $provider */
        $this->assertEquals('http://example.com/media/000000?type=large', (new SocialUser($user, $provider))->getAvatar());

        $user2 = m::mock(UserContract::class, function (MockInterface $mock) {
            $mock->shouldReceive('getAvatar')
                ->andReturn('http://example.com/media/000000');
        });

        $provider2 = m::mock(SocialEntryProvider::class, function (MockInterface $mock) {
            $mock->shouldReceive('getDriverName')->andReturn('google');
        });

        /** @var \Laravel\Socialite\Contracts\User $user2 */
        /** @var \A2Workspace\SocialEntry\Proxy\SocialEntryProvider $provider2 */
        $this->assertEquals('http://example.com/media/000000?sz=200', (new SocialUser($user2, $provider2))->getAvatar());
    }
}
