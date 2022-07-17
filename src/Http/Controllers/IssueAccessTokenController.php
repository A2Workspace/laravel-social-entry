<?php

namespace A2Workspace\SocialEntry\Http\Controllers;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use A2Workspace\SocialEntry\SocialEntry;
use A2Workspace\SocialEntry\AccessTokenPayload;
use A2Workspace\SocialEntry\Http\Requests\AuthCodeRequest;
use A2Workspace\SocialEntry\Exceptions\InvalidAuthorizationCodeException;
use A2Workspace\SocialEntry\Exceptions\InvalidAuthorizationCodePayloadException;

class IssueAccessTokenController
{
    /**
     * 將 authorization code 轉換為可存取資源的 access token
     *
     * @param  \A2Workspace\SocialEntry\Http\Requests\AuthCodeRequest|Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(AuthCodeRequest $request)
    {
        $encryptedAuthCode = $request->getAuthCode();

        try {
            $authCodePayload = json_decode(Crypt::decrypt($encryptedAuthCode));
        } catch (DecryptException $__) {
            throw new InvalidAuthorizationCodeException;
        }

        $this->validateAuthorizationCode($authCodePayload);

        // 非測試模式下註銷掉已被使用的 auth_code
        if (! app()->hasDebugModeEnabled()) {
            SocialEntry::authCodes()->revokeAuthCode($authCodePayload->auth_code_id);
        }

        return $this->respondAccessToken($authCodePayload);
    }

    /**
     * Validate the authorization payload.
     *
     * @param  stdClass  $authCodePayload
     * @return void
     */
    protected function validateAuthorizationCode($authCodePayload)
    {
        if (!property_exists($authCodePayload, 'auth_code_id')) {
            throw new InvalidAuthorizationCodePayloadException('Authorization code malformed');
        }

        if (time() > $authCodePayload->expire_time) {
            throw new InvalidAuthorizationCodePayloadException('Authorization code has expired');
        }

        $authCodeRecord = SocialEntry::authCodes()->findValidAuthCode($authCodePayload->auth_code_id);

        if (null === $authCodeRecord) {
            throw new InvalidAuthorizationCodePayloadException('Authorization code has been revoked');
        }

        if ($authCodeRecord->getAttribute('provider') !== $authCodePayload->provider) {
            throw new InvalidAuthorizationCodePayloadException('Invalid identifier');
        }

        if ($authCodeRecord->getAttribute('identifier') !== (string) $authCodePayload->identifier) {
            throw new InvalidAuthorizationCodePayloadException('Invalid identifier');
        }
    }

    /**
     * @param  stdClass $authCodePayload
     * @return \Illuminate\Http\Response
     */
    protected function respondAccessToken($authCodePayload)
    {
        $accessToken = SocialEntry::issueAccessToken(
            $authCodePayload->identifier,
            $authCodePayload->provider,
            $authCodePayload->scopes,
        );

        $localUser = SocialEntry::findLocalUser(
            $authCodePayload->identifier,
            $authCodePayload->provider,
        );

        $payload = AccessTokenPayload::parse([
            'provider'         => $authCodePayload->provider,
            'identifier'       => $authCodePayload->identifier,
            'social_email'     => $authCodePayload->social_email,
            'social_name'      => $authCodePayload->social_name,
            'social_avatar'    => $authCodePayload->social_avatar,
            'scopes'           => $authCodePayload->scopes,

            'access_token_id'  => $accessToken->getKey(),
            'expire_time'      => $accessToken->expires_at->timestamp,

            'local_user_id'    => $localUser ? $localUser->getKey() : null,
            'local_user_type'  => SocialEntry::userModel(),
        ]);

        $encodedAccessToken = Crypt::encrypt(json_encode($payload->toArray()));

        $responseParams = [
            'provider'         => $payload->provider,
            'identifier'       => $payload->identifier,
            'social_email'     => $payload->social_email,
            'social_name'      => $payload->social_name,
            'social_avatar'    => $payload->social_avatar,
            'scopes'           => $payload->scopes,

            'access_token'     => $encodedAccessToken,
            'expire_time'      => $payload->expire_time,

            'new_user'         => empty($payload->local_user_id),
            'local_user_id'    => $payload->local_user_id,
        ];

        return response($responseParams);
    }
}
