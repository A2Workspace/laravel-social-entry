<?php

namespace A2Workspace\SocialEntry\Entity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Auth\Authenticatable;

class IdentifierRepository
{
    /**
     * Make a new Identifier instance with given parameters.
     *
     * @param  \App\Models\User|\Illuminate\Contracts\Auth\Authenticatable  $localUser
     * @return \A2Workspace\SocialEntry\Entity\Identifier
     */
    public function newIdentifierFor(Authenticatable $localUser, $identifier, $type): Identifier
    {
        $newIdentifier = new Identifier;

        $newIdentifier->forceFill([
            'identifier' => $identifier,
            'type' => $type,
            'user_id' => $localUser->getKey(),
            'user_type' => get_class($localUser),
        ]);

        return $newIdentifier;
    }

    /**
     * @param  string  $identifier
     * @param  string  $type
     * @param  string  $userModel
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function queryIdentifiers($identifier, $type, $userModel): Builder
    {
        return Identifier::query()
            ->where('identifier', $identifier)
            ->where('type', $type)
            ->where('user_type', (string) $userModel);
    }

    /**
     * Check if the social user identifier exists.
     *
     * @param  string  $identifier
     * @param  string  $type
     * @param  string  $userModel
     * @return bool
     */
    public function exists($identifier, $type, $userModel): bool
    {
        return $this->queryIdentifiers($identifier, $type, $userModel)->exists();
    }

    /**
     * @param  string  $identifier
     * @param  string  $type
     * @param  string  $userModel
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function findLocalUser($identifier, $type, $userModel): ?Authenticatable
    {
        $identifier = $this->queryIdentifiers($identifier, $type, $userModel)->first();

        return $identifier ? $identifier->user : null;
    }
}
