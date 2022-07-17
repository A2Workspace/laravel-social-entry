<?php

namespace Tests\Feature;

use A2Workspace\SocialEntry\Exceptions\UnexpectedUserAuthorization;

class DisconnectUserTest extends GrantTestCase
{
    public function test_can_disconnect_by_access_token()
    {
        $user = \GrantTestCase\UserStub::create([
            'username' => 'user',
            'password' => '123456',
        ]);

        $user->socialIdentifiers()->create([
            'identifier' => 'social_id',
            'type' => 'auth',
        ]);

        $this->actingAs($user);

        $response = $this->call(
            'POST',
            '/auth/socialite/disconnect',
            [
                'type' => 'auth',
                'identifier' => 'social_id',
            ]
        );

        $response->assertOk();

        $this->assertDatabaseMissing('social_entry_identifiers', [
            'identifier'  => 'social_id',
            'type'        => 'auth',
            'user_id'     => $user->getKey(),
            'user_type'   => get_class($user),
        ]);

        $responsePayload = $response->decodeResponseJson();

        $this->assertArrayHasKey('status', $responsePayload);
        $this->assertArrayHasKey('message', $responsePayload);
        $this->assertEquals(true, $responsePayload['status']);
        $this->assertEquals('已解除綁定', $responsePayload['message']);
    }

    public function test_missing_parameters()
    {
        $response = $this->call(
            'POST',
            '/auth/socialite/disconnect',
            [
                'type' => 'auth',
            ]
        );

        $response->assertValid('type');
        $response->assertInvalid('identifier');

        $response2 = $this->call(
            'POST',
            '/auth/socialite/disconnect',
            [
                'identifier' => 'social_id',
            ]
        );

        $response2->assertValid('identifier');
        $response2->assertInvalid('type');
    }

    public function test_unexpected_user_logged_in()
    {
        $admin = \GrantTestCase\AdminStub::create([
            'username' => 'admin',
            'password' => '123456',
        ]);

        $this->actingAs($admin);

        $response = $this->call(
            'POST',
            '/auth/socialite/disconnect',
            [
                'type' => 'auth',
                'identifier' => 'social_id',
            ]
        );

        $response->assertStatus(403);
        $this->assertInstanceOf(UnexpectedUserAuthorization::class, $response->exception);
        $this->assertEquals(403, $response->exception->getStatusCode());
        $this->assertEquals('Unexpected user authorization', $response->exception->getMessage());
    }

    public function test_still_responses_successful_when_user_already_disconnected()
    {
        $user = \GrantTestCase\UserStub::create([
            'username' => 'user',
            'password' => '123456',
        ]);

        $this->actingAs($user);

        $response = $this->call(
            'POST',
            '/auth/socialite/disconnect',
            [
                'type' => 'auth',
                'identifier' => 'social_id',
            ]
        );

        $response->assertOk();

        $responsePayload = $response->decodeResponseJson();

        $this->assertArrayHasKey('status', $responsePayload);
        $this->assertArrayHasKey('message', $responsePayload);
        $this->assertEquals(false, $responsePayload['status']);
        $this->assertEquals('已解除綁定', $responsePayload['message']);
    }
}
