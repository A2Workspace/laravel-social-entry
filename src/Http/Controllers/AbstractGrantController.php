<?php

namespace A2Workspace\SocialEntry\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use A2Workspace\SocialEntry\SocialEntry;
use A2Workspace\SocialEntry\AccessTokenPayload;
use A2Workspace\SocialEntry\Http\Requests\AccessTokenRequest;
use A2Workspace\SocialEntry\Exceptions\InvalidAccessTokenException;
use A2Workspace\SocialEntry\Exceptions\InvalidAccessTokenPayloadException;

abstract class AbstractGrantController
{
    /**
     * 當前處理的 AccessTokenPayload 實例
     *
     * @var \A2Workspace\SocialEntry\AccessTokenPayload|null
     */
    protected ?AccessTokenPayload $accessTokenPayload = null;

    /**
     * 授權使用 access token 處理請求
     *
     * @param  \A2Workspace\SocialEntry\Http\Requests\AccessTokenRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(AccessTokenRequest $request)
    {
        $encryptedAccessToken = $request->getAccessToken();

        try {
            $accessTokenPayload = json_decode(Crypt::decrypt($encryptedAccessToken));
        } catch (DecryptException $__) {
            throw new InvalidAccessTokenException;
        }

        $this->validateAccessToken($accessTokenPayload);

        $this->accessTokenPayload = AccessTokenPayload::parse($accessTokenPayload);

        // 非測試模式下註銷掉已被使用的 access_token
        if (! app()->hasDebugModeEnabled()) {
            $this->revokeAccessToken();
        }

        return $this->completeRequestFromToken($request, $this->accessTokenPayload);
    }

    /**
     * Validate the access token payload.
     *
     * @param  stdClass  $accessTokenPayload
     * @return void
     *
     * @throws \A2Workspace\SocialEntry\Exceptions\InvalidAccessTokenPayloadException
     */
    protected function validateAccessToken($accessTokenPayload)
    {
        if (!property_exists($accessTokenPayload, 'access_token_id')) {
            throw new InvalidAccessTokenPayloadException('Access token malformed');
        }

        if (time() > $accessTokenPayload->expire_time) {
            throw new InvalidAccessTokenPayloadException('Access token has expired');
        }

        if (SocialEntry::userModel() !== $accessTokenPayload->local_user_type) {
            throw new InvalidAccessTokenPayloadException('Unexpected user authorization');
        }

        $accessTokenRecord = SocialEntry::accessTokens()->findValid($accessTokenPayload->access_token_id);

        if (null === $accessTokenRecord) {
            throw new InvalidAccessTokenPayloadException('Access token has been revoked');
        }

        if ($accessTokenRecord->getAttribute('provider') !== $accessTokenPayload->provider) {
            throw new InvalidAccessTokenPayloadException('Invalid identifier');
        }

        if ($accessTokenRecord->getAttribute('identifier') !== (string) $accessTokenPayload->identifier) {
            throw new InvalidAccessTokenPayloadException('Invalid identifier');
        }
    }

    /**
     * 註銷當前請求的 access_token
     *
     * @return bool
     */
    protected function revokeAccessToken()
    {
        return SocialEntry::accessTokens()->revokeAccessToken($this->accessTokenPayload);
    }

    /**
     * 完成授權請求
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \A2Workspace\SocialEntry\AccessTokenPayload  $accessTokenPayload
     * @return \Illuminate\Http\Response
     */
    abstract protected function completeRequestFromToken(Request $request, AccessTokenPayload $accessTokenPayload);
}
