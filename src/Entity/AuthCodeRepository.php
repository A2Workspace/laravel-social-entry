<?php

namespace A2Workspace\SocialEntry\Entity;

class AuthCodeRepository extends AbstractTokenRepository
{
    /**
     * {@inheritDoc}
     */
    public function getModelName(): string
    {
        return AuthCode::class;
    }

    /**
     * Proxy to create a new AuthCode model instance.
     *
     * @return \A2Workspace\SocialEntry\Entity\AuthCode
     */
    public function newAuthCode($expiresAt = null): AuthCode
    {
        return $this->newToken($expiresAt);
    }

    /**
     * Proxy to check if the auth code has been revoked.
     *
     * @param  \A2Workspace\SocialEntry\Entity\AuthCode|string  $authCode
     * @return bool
     */
    public function isAuthCodeRevoked($authCode): bool
    {
        return $this->isRevoked($authCode);
    }

    /**
     * Proxy to find a valid auth code record.
     *
     * @param  string  $code
     * @return \A2Workspace\SocialEntry\Entity\AuthCode|null
     */
    public function findValidAuthCode($code): ?AuthCode
    {
        return $this->findValid($code);
    }

    /**
     * Proxy to revoke the given AuthCode model instance.
     *
     * @param  mixed  $code
     * @return bool
     */
    public function revokeAuthCode($code)
    {
        return $this->revoke($code);
    }
}
