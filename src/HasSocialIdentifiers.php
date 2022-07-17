<?php

namespace A2Workspace\SocialEntry;

use A2Workspace\SocialEntry\Entity\Identifier;

trait HasSocialIdentifiers
{
    /**
     * Get the social identifier model that the user belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function socialIdentifiers()
    {
        return $this->morphMany(Identifier::class, 'user');
    }
}
