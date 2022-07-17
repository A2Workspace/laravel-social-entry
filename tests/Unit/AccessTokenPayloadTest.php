<?php

namespace Tests\Unit;

use A2Workspace\SocialEntry\AccessTokenPayload;

class AccessTokenPayloadTest extends TestCase
{
    /**
     * @return void
     */
    public function test_parse()
    {
        $payload = AccessTokenPayload::parse([
            'provider'         => 'github',
            'identifier'       => 'foobar',
            'social_email'     => 'foobar@example.com',
            'social_name'      => 'foo_bar',
            'social_avatar'    => 'http://example.com/images/foobar',
            'scopes'           => null,

            'access_token_id'  => 'foo_bar_000',
            'expire_time'      => 1654012800,

            'local_user_id'    => 'foobar',
            'local_user_type'  => 'App\\Models\\User',
        ]);

        $this->assertEquals('github', $payload->provider);
        $this->assertEquals('foobar', $payload->identifier);
        $this->assertEquals('foobar@example.com', $payload->social_email);
        $this->assertEquals('foobar@example.com', $payload->socialEmail);
        $this->assertEquals('foo_bar', $payload->social_name);
        $this->assertEquals('foo_bar', $payload->socialName);
        $this->assertEquals('http://example.com/images/foobar', $payload->social_avatar);
        $this->assertEquals('http://example.com/images/foobar', $payload->socialAvatar);
        $this->assertEquals(null, $payload->scopes);

        $this->assertEquals('foo_bar_000', $payload->access_token_id);
        $this->assertEquals('foo_bar_000', $payload->accessTokenId);
        $this->assertEquals(1654012800, $payload->expire_time);
        $this->assertEquals(1654012800, $payload->expireTime);

        $this->assertEquals('foobar', $payload->local_user_id);
        $this->assertEquals('foobar', $payload->localUserId);
        $this->assertEquals('App\\Models\\User', $payload->local_user_type);
        $this->assertEquals('App\\Models\\User', $payload->localUserType);
    }

    /**
     * @return void
     */
    public function test_toArray()
    {
        $expected = [
            'provider'         => 'github',
            'identifier'       => 'foobar',
            'social_email'     => 'foobar@example.com',
            'social_name'      => 'foo_bar',
            'social_avatar'    => 'http://example.com/images/foobar',
            'scopes'           => null,

            'access_token_id'  => 'foo_bar_000',
            'expire_time'      => 1654012800,

            'local_user_id'    => 'foobar',
            'local_user_type'  => 'App\\Models\\User',
        ];

        $payload = new AccessTokenPayload;

        $payload->provider = $expected['provider'];
        $payload->identifier = $expected['identifier'];
        $payload->social_email = $expected['social_email'];
        $payload->social_name = $expected['social_name'];
        $payload->social_avatar = $expected['social_avatar'];
        $payload->scopes = $expected['scopes'];

        $payload->access_token_id = $expected['access_token_id'];
        $payload->expire_time = $expected['expire_time'];

        $payload->local_user_id = $expected['local_user_id'];
        $payload->local_user_type = $expected['local_user_type'];

        $this->assertEquals($expected, $payload->toArray());
    }
}
