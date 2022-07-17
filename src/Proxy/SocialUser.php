<?php

namespace A2Workspace\SocialEntry\Proxy;

use Laravel\Socialite\Contracts\User as UserContract;
use A2Workspace\SocialEntry\Contracts\SocialUser as SocialUserContract;

class SocialUser implements SocialUserContract
{
    /**
     * The real Socialite User instance.
     *
     * @var \Laravel\Socialite\Contracts\User
     */
    private UserContract $user;

    /**
     * The SocialEntryProvider instance.
     *
     * @var \A2Workspace\SocialEntry\Proxy\SocialEntryProvider
     */
    private SocialEntryProvider $provider;

    /**
     * @param  \Laravel\Socialite\Contracts\User  $user
     * @param  \A2Workspace\SocialEntry\Proxy\SocialEntryProvider  $provider
     */
    public function __construct(UserContract $user, SocialEntryProvider $provider)
    {
        $this->user = $user;
        $this->provider = $provider;
    }

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return $this->user->getId();
    }

    /**
     * {@inheritDoc}
     */
    public function getNickname()
    {
        return $this->user->getNickname();
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->user->getName();
    }

    /**
     * {@inheritDoc}
     */
    public function getEmail()
    {
        return $this->user->getEmail();
    }

    /**
     * {@inheritDoc}
     */
    public function getAvatar()
    {
        $avatar = $this->user->getAvatar();

        switch ($this->provider->getDriverName()) {
            case 'facebook':
                return str_replace('type=normal', 'type=large', $avatar);

            case 'google':
                return "{$avatar}?sz=200";

            default:
                return $avatar;
        }
    }

    /**
     * Return the SocialEntryProvider instance.
     *
     * @return \A2Workspace\SocialEntry\Proxy\SocialEntryProvider
     */
    public function getProvider(): SocialEntryProvider
    {
        return $this->provider;
    }

    /**
     * {@inheritDoc}
     */
    public function getProviderName()
    {
        return $this->getProvider()->getDriverName();
    }
}
