<?php

namespace Tests\Feature;

use A2Workspace\SocialEntry\Entity\AuthCodeRepository;
use A2Workspace\SocialEntry\Entity\IdentifierRepository;
use A2Workspace\SocialEntry\Entity\AuthCode as AuthCodeRecord;
use A2Workspace\SocialEntry\Exceptions\InvalidAuthorizationCodeException;
use A2Workspace\SocialEntry\Exceptions\InvalidAuthorizationCodePayloadException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Mockery as m;
use Mockery\MockInterface;

class IssueAccessTokenTest extends TestCase
{
    use DatabaseMigrations;

    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }

    private function makeAuthCode(array $payload): string
    {
        return Crypt::encrypt(json_encode($payload));
    }

    public function test_getting_access_token_with_auth_code_requests()
    {
        $this->instance(
            AuthCodeRepository::class,
            m::mock(AuthCodeRepository::class, function (MockInterface $mock) {
                $record = new AuthCodeRecord;
                $record->provider = 'auth';
                $record->identifier = 'social_id';

                $mock->shouldReceive('findValidAuthCode')
                    ->with('auth_code_id')
                    ->andReturn($record);

                $mock->shouldReceive('revokeAuthCode')
                    ->with('auth_code_id')
                    ->andReturn(true);
            })
        );

        $this->instance(
            IdentifierRepository::class,
            m::mock(IdentifierRepository::class, function (MockInterface $mock) {
                $mock->shouldReceive('findLocalUser')
                    ->andReturn(null);
            })
        );

        $authCode = $this->makeAuthCode([
            'provider' => 'auth',
            'identifier' => 'social_id',
            'social_email' => 'social_email',
            'social_name' => 'social_name',
            'social_avatar' => 'social_avatar',
            'auth_code_id' => 'auth_code_id',
            'expire_time' => Carbon::parse('+ 5 minutes')->getTimestamp(),
            'scopes' => 'scopes',
        ]);

        $response = $this->call(
            'POST',
            '/auth/socialite/token',
            ['code' => $authCode]
        );

        $response->assertSuccessful();

        $this->assertDatabaseHas('social_entry_access_tokens', [
            'identifier' => 'social_id',
            'provider' => 'auth',
            'revoked' => false,
        ]);

        $responsePayload = $response->decodeResponseJson();

        $this->assertArrayHasKey('provider', $responsePayload);
        $this->assertArrayHasKey('identifier', $responsePayload);
        $this->assertArrayHasKey('social_email', $responsePayload);
        $this->assertArrayHasKey('social_name', $responsePayload);
        $this->assertArrayHasKey('social_avatar', $responsePayload);

        $this->assertArrayHasKey('access_token', $responsePayload);
        $this->assertArrayHasKey('expire_time', $responsePayload);
        $this->assertArrayHasKey('new_user', $responsePayload);

        $this->assertEquals('auth', $responsePayload['provider']);
        $this->assertEquals('social_id', $responsePayload['identifier']);
        $this->assertEquals('social_email', $responsePayload['social_email']);
        $this->assertEquals('social_name', $responsePayload['social_name']);
        $this->assertEquals('social_avatar', $responsePayload['social_avatar']);
    }

    public function test_missing_code_parameter()
    {
        $response = $this->call(
            'POST',
            '/auth/socialite/token'
        );

        $response->assertInvalid('code');
    }

    public function test_thrown_when_decode_failed()
    {
        $response = $this->call(
            'POST',
            '/auth/socialite/token',
            ['code' => 'INVALID_AUTH_CODE']
        );

        $response->assertStatus(400);

        $this->assertInstanceOf(HttpException::class, $response->exception);
        $this->assertInstanceOf(InvalidAuthorizationCodeException::class, $response->exception);
        $this->assertEquals(400, $response->exception->getStatusCode());
        $this->assertEquals('Invalid authorization code', $response->exception->getMessage());
    }

    public function test_missing_auth_code_id_in_payload()
    {
        $authCode = $this->makeAuthCode([
            'expire_time' => Carbon::parse('+ 5 minutes')->getTimestamp(),
            'provider' => 'auth',
            'identifier' => 'social_id',
        ]);

        $response = $this->call(
            'POST',
            '/auth/socialite/token',
            ['code' => $authCode]
        );

        $response->assertStatus(400);

        $this->assertInstanceOf(HttpException::class, $response->exception);
        $this->assertInstanceOf(InvalidAuthorizationCodePayloadException::class, $response->exception);
        $this->assertEquals(400, $response->exception->getStatusCode());
        $this->assertEquals('Authorization code malformed', $response->exception->getMessage());
    }

    public function test_auth_code_is_expired()
    {
        $authCode = $this->makeAuthCode([
            'auth_code_id' => 'auth_code_id',
            'expire_time' => Carbon::parse('- 10 minutes')->getTimestamp(),
            'provider' => 'auth',
            'identifier' => 'social_id',
        ]);

        $response = $this->call(
            'POST',
            '/auth/socialite/token',
            ['code' => $authCode]
        );

        $response->assertStatus(400);

        $this->assertInstanceOf(HttpException::class, $response->exception);
        $this->assertInstanceOf(InvalidAuthorizationCodePayloadException::class, $response->exception);
        $this->assertEquals(400, $response->exception->getStatusCode());
        $this->assertEquals('Authorization code has expired', $response->exception->getMessage());
    }

    public function test_auth_code_has_been_revoked()
    {
        $this->instance(
            AuthCodeRepository::class,
            m::mock(AuthCodeRepository::class, function (MockInterface $mock) {
                $mock->shouldReceive('findValidAuthCode')
                    ->once()
                    ->andReturn(null);
            })
        );

        $authCode = $this->makeAuthCode([
            'auth_code_id' => 'auth_code_id',
            'expire_time' => Carbon::parse('+ 5 minutes')->getTimestamp(),
            'provider' => 'auth',
            'identifier' => 'social_id',
        ]);

        $response = $this->call(
            'POST',
            '/auth/socialite/token',
            ['code' => $authCode]
        );

        $response->assertStatus(400);

        $this->assertInstanceOf(HttpException::class, $response->exception);
        $this->assertInstanceOf(InvalidAuthorizationCodePayloadException::class, $response->exception);
        $this->assertEquals(400, $response->exception->getStatusCode());
        $this->assertEquals('Authorization code has been revoked', $response->exception->getMessage());
    }

    public function test_mismatch_auth_code_provider()
    {
        $this->instance(
            AuthCodeRepository::class,
            m::mock(AuthCodeRepository::class, function (MockInterface $mock) {
                $record = new AuthCodeRecord;
                $record->provider = 'auth';
                $record->identifier = 'social_id';

                $mock->shouldReceive('findValidAuthCode')->andReturn($record);
            })
        );

        $authCode = $this->makeAuthCode([
            'auth_code_id' => 'auth_code_id',
            'expire_time' => Carbon::parse('+ 5 minutes')->getTimestamp(),
            'provider' => 'INVALID_PROVIDER',
            'identifier' => 'social_id',
        ]);

        $response = $this->call(
            'POST',
            '/auth/socialite/token',
            ['code' => $authCode]
        );

        $response->assertStatus(400);

        $this->assertInstanceOf(HttpException::class, $response->exception);
        $this->assertInstanceOf(InvalidAuthorizationCodePayloadException::class, $response->exception);
        $this->assertEquals(400, $response->exception->getStatusCode());
        $this->assertEquals('Invalid identifier', $response->exception->getMessage());
    }

    public function test_mismatch_auth_code_identifier()
    {
        $this->instance(
            AuthCodeRepository::class,
            m::mock(AuthCodeRepository::class, function (MockInterface $mock) {
                $record = new AuthCodeRecord;
                $record->provider = 'auth';
                $record->identifier = 'social_id';

                $mock->shouldReceive('findValidAuthCode')->andReturn($record);
            })
        );

        $authCode = $this->makeAuthCode([
            'auth_code_id' => 'auth_code_id',
            'expire_time' => Carbon::parse('+ 5 minutes')->getTimestamp(),
            'provider' => 'auth',
            'identifier' => 'INVALID_SOCIAL_ID',
        ]);

        $response = $this->call(
            'POST',
            '/auth/socialite/token',
            ['code' => $authCode]
        );

        $response->assertStatus(400);

        $this->assertInstanceOf(HttpException::class, $response->exception);
        $this->assertInstanceOf(InvalidAuthorizationCodePayloadException::class, $response->exception);
        $this->assertEquals(400, $response->exception->getStatusCode());
        $this->assertEquals('Invalid identifier', $response->exception->getMessage());
    }
}
