<?php

namespace Tests\Feature;

use A2Workspace\SocialEntry\SocialEntry;
use A2Workspace\SocialEntry\AccessTokenPayload;
use A2Workspace\SocialEntry\Entity\AccessTokenRepository;
use A2Workspace\SocialEntry\Entity\AccessToken as AccessTokenRecord;
use A2Workspace\SocialEntry\Exceptions\InvalidAccessTokenException;
use A2Workspace\SocialEntry\Exceptions\InvalidAccessTokenPayloadException;
use A2Workspace\SocialEntry\Http\Requests\AccessTokenRequest;
use A2Workspace\SocialEntry\Http\Controllers\AbstractGrantController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use Mockery as m;
use Mockery\MockInterface;

class AbstractGrantControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        SocialEntry::useUserModel(\AbstractGrantControllerTest\UserStub::class);
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

    public function test_handling_with_access_token_requests()
    {
        $this->instance(
            AccessTokenRepository::class,
            m::mock(AccessTokenRepository::class, function (MockInterface $mock) {
                $record = new AccessTokenRecord;
                $record->provider = 'auth';
                $record->identifier = 'social_id';

                $mock->shouldReceive('findValid')
                    ->with('access_token_id')
                    ->andReturn($record);
            })
        );

        $accessToken = $this->makeAccessToken([
            'provider' => 'auth',
            'identifier' => 'social_id',
            'social_email' => 'social_email',
            'social_name' => 'social_name',
            'social_avatar' => 'social_avatar',
            'access_token_id'  => 'access_token_id',
            'expire_time'      => Carbon::parse('+ 5 minutes')->getTimestamp(),
            'local_user_id'    => null,
            'local_user_type'  => \AbstractGrantControllerTest\UserStub::class,
        ]);

        $request = m::mock(
            AccessTokenRequest::class,
            function (MockInterface $mock) use ($accessToken) {
                $mock->shouldReceive('getAccessToken')
                    ->andReturn($accessToken);
            }
        );

        $response = (new GrantControllerStub)($request);

        $responsePayload = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('provider', $responsePayload);
        $this->assertArrayHasKey('identifier', $responsePayload);
        $this->assertArrayHasKey('social_email', $responsePayload);
        $this->assertArrayHasKey('social_name', $responsePayload);
        $this->assertArrayHasKey('social_avatar', $responsePayload);

        $this->assertArrayHasKey('access_token_id', $responsePayload);
        $this->assertArrayHasKey('expire_time', $responsePayload);
        $this->assertArrayHasKey('local_user_id', $responsePayload);
        $this->assertArrayHasKey('local_user_type', $responsePayload);

        $this->assertEquals('auth', $responsePayload['provider']);
        $this->assertEquals('social_id', $responsePayload['identifier']);
        $this->assertEquals('social_email', $responsePayload['social_email']);
        $this->assertEquals('social_name', $responsePayload['social_name']);
        $this->assertEquals('social_avatar', $responsePayload['social_avatar']);
        $this->assertEquals('access_token_id', $responsePayload['access_token_id']);
        $this->assertEquals(\AbstractGrantControllerTest\UserStub::class, $responsePayload['local_user_type']);
    }

    public function test_access_token_decode_failed()
    {
        $this->expectException(InvalidAccessTokenException::class);

        Crypt::shouldReceive('decrypt')
            ->with('INVALID_ACCESS_TOKEN')
            ->andReturnUsing(function () {
                throw new DecryptException;
            });

        $request = m::mock(
            AccessTokenRequest::class,
            function (MockInterface $mock) {
                $mock->shouldReceive('getAccessToken')->andReturn('INVALID_ACCESS_TOKEN');
            }
        );

        (new GrantControllerStub)($request);
    }

    public function test_missing_access_token_id_in_payload()
    {
        $this->expectException(InvalidAccessTokenPayloadException::class);
        $this->expectExceptionMessage('Access token malformed');

        $accessToken = $this->makeAccessToken([
            'expire_time' => Carbon::parse('+ 5 minutes')->getTimestamp(),
            'local_user_type' => \AbstractGrantControllerTest\UserStub::class,
            'provider' => 'auth',
            'identifier' => 'social_id',
        ]);

        $request = m::mock(
            AccessTokenRequest::class,
            function (MockInterface $mock) use ($accessToken) {
                $mock->shouldReceive('getAccessToken')->andReturn($accessToken);
            }
        );

        (new GrantControllerStub)($request);
    }

    public function test_access_token_expired()
    {
        $this->expectException(InvalidAccessTokenPayloadException::class);
        $this->expectExceptionMessage('Access token has expired');

        $accessToken = $this->makeAccessToken([
            'access_token_id' => 'access_token_id',
            'expire_time' => Carbon::parse('- 10 minutes')->getTimestamp(),
            'local_user_type' => \AbstractGrantControllerTest\UserStub::class,
            'provider' => 'auth',
            'identifier' => 'social_id',
        ]);

        $request = m::mock(
            AccessTokenRequest::class,
            function (MockInterface $mock) use ($accessToken) {
                $mock->shouldReceive('getAccessToken')->andReturn($accessToken);
            }
        );

        (new GrantControllerStub)($request);
    }

    public function test_mismatch_access_token_user_type()
    {
        $this->expectException(InvalidAccessTokenPayloadException::class);
        $this->expectExceptionMessage('Unexpected user authorization');

        $accessToken = $this->makeAccessToken([
            'access_token_id' => 'access_token_id',
            'expire_time' => Carbon::parse('+ 5 minutes')->getTimestamp(),
            'local_user_type' => 'INVALID_USER_TYPE',
            'provider' => 'auth',
            'identifier' => 'social_id',
        ]);

        $request = m::mock(
            AccessTokenRequest::class,
            function (MockInterface $mock) use ($accessToken) {
                $mock->shouldReceive('getAccessToken')->andReturn($accessToken);
            }
        );

        (new GrantControllerStub)($request);
    }

    public function test_access_token_has_been_revoked()
    {
        $this->expectException(InvalidAccessTokenPayloadException::class);
        $this->expectExceptionMessage('Access token has been revoked');

        $this->instance(
            AccessTokenRepository::class,
            m::mock(AccessTokenRepository::class, function (MockInterface $mock) {
                $mock->shouldReceive('findValid')->andReturn(null);
            })
        );

        $accessToken = $this->makeAccessToken([
            'access_token_id' => 'access_token_id',
            'expire_time' => Carbon::parse('+ 5 minutes')->getTimestamp(),
            'local_user_type' => \AbstractGrantControllerTest\UserStub::class,
            'provider' => 'auth',
            'identifier' => 'social_id',
        ]);

        $request = m::mock(
            AccessTokenRequest::class,
            function (MockInterface $mock) use ($accessToken) {
                $mock->shouldReceive('getAccessToken')->andReturn($accessToken);
            }
        );

        (new GrantControllerStub)($request);
    }

    public function test_mismatch_access_token_provider()
    {
        $this->expectException(InvalidAccessTokenPayloadException::class);
        $this->expectExceptionMessage('Invalid identifier');

        $this->instance(
            AccessTokenRepository::class,
            m::mock(AccessTokenRepository::class, function ($mock) {
                $record = new AccessTokenRecord;
                $record->provider = 'auth';
                $record->identifier = 'social_id';

                $mock->shouldReceive('findValid')->andReturn($record);
            })
        );

        $accessToken = $this->makeAccessToken([
            'access_token_id' => 'access_token_id',
            'expire_time' => Carbon::parse('+ 5 minutes')->getTimestamp(),
            'local_user_type' => \AbstractGrantControllerTest\UserStub::class,
            'provider' => 'INVALID_PROVIDER',
            'identifier' => 'social_id',
        ]);

        $request = m::mock(
            AccessTokenRequest::class,
            function (MockInterface $mock) use ($accessToken) {
                $mock->shouldReceive('getAccessToken')->andReturn($accessToken);
            }
        );

        (new GrantControllerStub)($request);
    }

    public function test_mismatch_access_token_identifier()
    {
        $this->expectException(InvalidAccessTokenPayloadException::class);
        $this->expectExceptionMessage('Invalid identifier');

        $this->instance(
            AccessTokenRepository::class,
            m::mock(AccessTokenRepository::class, function ($mock) {
                $record = new AccessTokenRecord;
                $record->provider = 'auth';
                $record->identifier = 'social_id';

                $mock->shouldReceive('findValid')->andReturn($record);
            })
        );

        $accessToken = $this->makeAccessToken([
            'access_token_id' => 'access_token_id',
            'expire_time' => Carbon::parse('+ 5 minutes')->getTimestamp(),
            'local_user_type' => \AbstractGrantControllerTest\UserStub::class,
            'provider' => 'auth',
            'identifier' => 'INVALID_SOCIAL_ID',
        ]);

        $request = m::mock(
            AccessTokenRequest::class,
            function (MockInterface $mock) use ($accessToken) {
                $mock->shouldReceive('getAccessToken')->andReturn($accessToken);
            }
        );

        (new GrantControllerStub)($request);
    }
}

class GrantControllerStub extends AbstractGrantController
{
    protected function completeRequestFromToken(Request $request, AccessTokenPayload $accessTokenPayload)
    {
        return new Response($accessTokenPayload->toArray());
    }

    protected function revokeAccessToken()
    {
        return true;
    }
}

namespace AbstractGrantControllerTest;

class UserStub extends \Illuminate\Database\Eloquent\Model
{
    // ...
}
