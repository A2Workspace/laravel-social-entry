<?php

namespace A2Workspace\SocialEntry\Entity;

use A2Workspace\SocialEntry\AccessTokenPayload;

class AccessTokenRepository extends AbstractTokenRepository
{
    /**
     * {@inheritDoc}
     */
    public function getModelName(): string
    {
        return AccessToken::class;
    }

    /**
     * 註銷掉已發行的 access token
     *
     * @param  \A2Workspace\SocialEntry\AccessTokenPayload|string  $token
     * @return bool
     */
    public function revokeAccessToken($token)
    {
        if ($token instanceof AccessTokenPayload) {
            return $this->revoke($token->accessTokenId);
        }

        return $this->revoke($token);
    }
}
