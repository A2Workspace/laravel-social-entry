<?php

namespace Tests\Feature;

use A2Workspace\SocialEntry\SocialEntry;
use A2Workspace\SocialEntry\Entity\AccessTokenRepository;
use A2Workspace\SocialEntry\Entity\AccessToken as AccessTokenRecord;
use A2Workspace\SocialEntry\Exceptions\UnexpectedUserAuthorization;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Mockery as m;
use Mockery\MockInterface;

class ConnectUserTest extends GrantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_can_connect_social_account_and_local_user_by_access_token()
    {
        $user = \GrantTestCase\UserStub::create([
            'username' => 'user',
            'password' => '123456',
        ]);

        $accessToken = $this->makeAccessToken([
            'provider'         => 'auth',
            'identifier'       => 'social_id',
            'social_email'     => 'social_email',
            'social_name'      => 'social_name',
            'social_avatar'    => 'social_avatar',
            'scopes'           => 'scopes',

            'access_token_id'  => 'access_token_id',
            'expire_time'      => Carbon::parse('+ 2 weeks')->getTimestamp(),

            'local_user_id'    => $user->getKey(),
            'local_user_type'  => get_class($user),
        ]);

        $this->actingAs($user);
        $response = $this->call(
            'POST',
            '/auth/socialite/connect',
            ['access_token' => $accessToken]
        );

        $response->assertOk();

        $this->assertDatabaseHas('social_entry_identifiers', [
            'identifier'  => 'social_id',
            'type'        => 'auth',
            'user_id'     => $user->getKey(),
            'user_type'   => get_class($user),
        ]);

        $responsePayload = $response->decodeResponseJson();

        $this->assertArrayHasKey('status', $responsePayload);
        $this->assertArrayHasKey('message', $responsePayload);
        $this->assertEquals(true, $responsePayload['status']);
        $this->assertEquals('已成功綁定', $responsePayload['message']);
    }

    public function test_thrown_when_user_not_logged_in()
    {
        $accessToken = $this->makeAccessToken([
            'provider'         => 'auth',
            'identifier'       => 'social_id',
            'social_email'     => 'social_email',
            'social_name'      => 'social_name',
            'social_avatar'    => 'social_avatar',
            'scopes'           => 'scopes',

            'access_token_id'  => 'access_token_id',
            'expire_time'      => Carbon::parse('+ 2 weeks')->getTimestamp(),

            'local_user_id'    => 1,
            'local_user_type'  => \GrantTestCase\UserStub::class,
        ]);

        $response = $this->call(
            'POST',
            '/auth/socialite/connect',
            ['access_token' => $accessToken]
        );

        $response->assertStatus(401);
        $this->assertInstanceOf(HttpException::class, $response->exception);
        $this->assertEquals(401, $response->exception->getStatusCode());

    }

    public function test_thrown_when_user_type_mismatch()
    {
        $admin = \GrantTestCase\AdminStub::create([
            'username' => 'admin',
            'password' => '123456',
        ]);

        $accessToken = $this->makeAccessToken([
            'provider'         => 'auth',
            'identifier'       => 'social_id',
            'social_email'     => 'social_email',
            'social_name'      => 'social_name',
            'social_avatar'    => 'social_avatar',
            'scopes'           => 'scopes',

            'access_token_id'  => 'access_token_id',
            'expire_time'      => Carbon::parse('+ 2 weeks')->getTimestamp(),

            'local_user_id'    => 1,
            'local_user_type'  => \GrantTestCase\UserStub::class,
        ]);

        $this->actingAs($admin);
        $response = $this->call(
            'POST',
            '/auth/socialite/connect',
            ['access_token' => $accessToken]
        );

        $response->assertStatus(403);
        $this->assertInstanceOf(UnexpectedUserAuthorization::class, $response->exception);
        $this->assertEquals(403, $response->exception->getStatusCode());
        $this->assertEquals('Unexpected user authorization', $response->exception->getMessage());
    }

    public function test_still_responses_successful_when_user_already_connected()
    {
        $user = \GrantTestCase\UserStub::create([
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
            'social_email'     => 'social_email',
            'social_name'      => 'social_name',
            'social_avatar'    => 'social_avatar',
            'scopes'           => 'scopes',

            'access_token_id'  => 'access_token_id',
            'expire_time'      => Carbon::parse('+ 2 weeks')->getTimestamp(),

            'local_user_id'    => $user->getKey(),
            'local_user_type'  => get_class($user),
        ]);

        $this->actingAs($user);
        $response = $this->call(
            'POST',
            '/auth/socialite/connect',
            ['access_token' => $accessToken]
        );

        $response->assertOk();

        $responsePayload = $response->decodeResponseJson();

        $this->assertArrayHasKey('status', $responsePayload);
        $this->assertArrayHasKey('message', $responsePayload);
        $this->assertEquals(false, $responsePayload['status']);
        $this->assertEquals('該帳號已被綁定', $responsePayload['message']);
    }
}
