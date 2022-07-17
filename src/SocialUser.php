<?php

namespace A2Workspace\SocialEntry;

use A2Workspace\SocialEntry\Contracts\SocialUser as SocialUserContract;

class SocialUser implements SocialUserContract
{
    /**
     * The name of socialite provider.
     *
     * @var string
     */
    public $provider;

    /**
     * The user's identifier attribute.
     *
     * @var mixed
     */
    public $id;

    /**
     * The user's nickname attribute.
     *
     * @var string
     */
    public $nickname;

    /**
     * The user's full name attribute.
     *
     * @var string
     */
    public $name;

    /**
     * The user's e-mail attribute.
     *
     * @var string
     */
    public $email;

    /**
     * The user's avatar attribute.
     *
     * @var string
     */
    public $avatar;

    /**
     * {@inheritDoc}
     */
    public function getProviderName()
    {
        return $this->provider;
    }

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function getNickname()
    {
        return $this->nickname;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * {@inheritDoc}
     */
    public function getAvatar()
    {
        return $this->avatar;
    }
}
