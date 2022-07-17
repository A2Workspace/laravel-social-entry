<?php

namespace A2Workspace\SocialEntry\Http\Controllers;

use Illuminate\Http\Request;
use A2Workspace\SocialEntry\SocialEntry;
use A2Workspace\SocialEntry\AccessTokenPayload;
use A2Workspace\SocialEntry\Exceptions\CannotCreateUserAccessTokenException;

class AuthenticationController extends AbstractGrantController
{
    /**
     * 透過 socialEntry 的 access_token 頒布一般登入用的 access_token。
     */
    protected function completeRequestFromToken(Request $request, AccessTokenPayload $accessTokenPayload)
    {
        $user = SocialEntry::findLocalUser(
            $accessTokenPayload->identifier,
            $accessTokenPayload->provider,
        );

        if (is_null($user)) {
            abort(403);
        }

        if (! method_exists($user, 'createToken')) {
            throw new CannotCreateUserAccessTokenException(
                sprintf('The "createToken" method must be declared in %s', get_class($user))
            );
        }

        $token = $user->createToken();

        return $this->respondUserAccessToken($token);
    }

    /**
     * @param  string  $token
     * @return \Illuminate\Http\Response
     */
    protected function respondUserAccessToken($token)
    {
        return response([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => 60 * 60,
        ]);
    }
}
