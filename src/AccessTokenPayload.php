<?php

namespace A2Workspace\SocialEntry;

use A2Workspace\SocialEntry\Contracts\DataAccess;
use A2Workspace\SocialEntry\Concerns\DataAccessTrait;

class AccessTokenPayload implements DataAccess
{
    use DataAccessTrait;

    /**
     * @var string
     */
    public $provider;

    /**
     * @var string
     */
    public $identifier;

    /**
     * @var string
     */
    public $socialEmail;

    /**
     * @var string
     */
    public $socialName;

    /**
     * @var string
     */
    public $socialAvatar;

    /**
     * @var string
     */
    public $scopes;

    /**
     * @var string
     */
    public $accessTokenId;

    /**
     * @var string
     */
    public $expireTime;

    /**
     * @var string
     */
    public $localUserId;

    /**
     * @var string
     */
    public $localUserType;
}
