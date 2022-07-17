<?php

namespace Tests\Feature;

use A2Workspace\SocialEntry\SocialEntry;
use A2Workspace\SocialEntry\Entity\AccessTokenRepository;
use A2Workspace\SocialEntry\Entity\AccessToken as AccessTokenRecord;
use A2Workspace\SocialEntry\Exceptions\CannotCreateUserAccessTokenException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Mockery as m;
use Mockery\MockInterface;

class LoginTest extends GrantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        SocialEntry::useUserModel(\LoginTest\UserStub::class);

        $this->instance(
            AccessTokenRepository::class,
            m::mock(AccessTokenRepository::class, function (MockInterface $mock) {
                $record = new AccessTokenRecord;
                $record->provider = 'auth';
                $record->identifier = 'social_id';

                $mock->shouldReceive('findValid')
                    ->once()
                    ->with('access_token_id')
                    ->andReturn($record);

                $mock->shouldReceive('revokeAccessToken')
                    ->once()
                    ->andReturn(true);
            })
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }

    private function makeAccessToken(array $payload): string
    {
        return Crypt::encrypt(json_encode($payload));
    }

    public function test_grant_jwt_token()
    {
        $user = \LoginTest\UserStub::create([
            'username' => 'user',
            'password' => '123456',
        ]);

        $user->socialIdentifiers()->create([
            'identifier' => 'social_id',
            'type' => 'auth',
        ]);

        $accessToken = $this->makeAccessToken([
            'provider'         => 'auth',
            'identifier'       => 'social_id',

            'access_token_id'  => 'access_token_id',
            'expire_time'      => Carbon::parse('+ 2 weeks')->getTimestamp(),

            'local_user_id'    => $user->getKey(),
            'local_user_type'  => get_class($user),
        ]);

        $response = $this->call(
            'POST',
            '/auth/socialite/login',
            ['access_token' => $accessToken]
        );

        $response->assertOk();

        $responsePayload = $response->decodeResponseJson();

        $this->assertArrayHasKey('access_token', $responsePayload);
        $this->assertArrayHasKey('token_type', $responsePayload);
        $this->assertArrayHasKey('expires_in', $responsePayload);

        $this->assertEquals('jwt_access_token', $responsePayload['access_token']);
        $this->assertEquals('bearer', $responsePayload['token_type']);
        $this->assertEquals(3600, $responsePayload['expires_in']);
    }

    public function test_throws_when_user_was_not_connected()
    {
        $accessToken = $this->makeAccessToken([
            'provider'         => 'auth',
            'identifier'       => 'social_id',

            'access_token_id'  => 'access_token_id',
            'expire_time'      => Carbon::parse('+ 2 weeks')->getTimestamp(),

            'local_user_id'    => 'INVALID_USER_ID',
            'local_user_type'  => \LoginTest\UserStub::class,
        ]);

        $response = $this->call(
            'POST',
            '/auth/socialite/login',
            ['access_token' => $accessToken]
        );

        $response->assertStatus(403);
        $this->assertInstanceOf(HttpException::class, $response->exception);
        $this->assertEquals(403, $response->exception->getStatusCode());
    }

    public function test_the_creating_token_method_has_not_been_declared_in_user_model()
    {
        SocialEntry::useUserModel(\LoginTest\IncompleteUserStub::class);

        $user = \LoginTest\IncompleteUserStub::create([
            'username' => 'user',
            'password' => '123456',
        ]);

        $user->socialIdentifiers()->create([
            'identifier' => 'social_id',
            'type' => 'auth',
        ]);

        $accessToken = $this->makeAccessToken([
            'provider'         => 'auth',
            'identifier'       => 'social_id',

            'access_token_id'  => 'access_token_id',
            'expire_time'      => Carbon::parse('+ 2 weeks')->getTimestamp(),

            'local_user_id'    => 'INVALID_USER_ID',
            'local_user_type'  => \LoginTest\IncompleteUserStub::class,
        ]);

        $this->actingAs($user);

        $response = $this->call(
            'POST',
            '/auth/socialite/login',
            ['access_token' => $accessToken]
        );

        $response->assertStatus(500);
        $this->assertInstanceOf(CannotCreateUserAccessTokenException::class, $response->exception);
        $this->assertEquals(500, $response->exception->getStatusCode());
        $this->assertEquals('The "createToken" method must be declared in LoginTest\IncompleteUserStub', $response->exception->getMessage());
    }
}

namespace LoginTest;

class UserStub extends \GrantTestCase\UserStub
{
    public function createToken()
    {
        return 'jwt_access_token';
    }
}

class IncompleteUserStub extends \GrantTestCase\UserStub
{
    // ...
}
