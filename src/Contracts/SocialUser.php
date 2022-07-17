<?php

namespace A2Workspace\SocialEntry\Contracts;

use Laravel\Socialite\Contracts\User as UserContract;

interface SocialUser extends UserContract
{
    /**
     * Return the provider name.
     *
     * @return string
     */
    public function getProviderName();
}
