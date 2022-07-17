<?php

namespace A2Workspace\SocialEntry\Entity;

use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;

abstract class AbstractTokenRepository
{
    /**
     * Define the expiration time for tokens.
     *
     * @var string
     */
    public static $defaultExpiresAt = '5 minutes';

    /**
     * Return the token model name of repository.
     *
     * @return string
     */
    abstract public function getModelName(): string;

    /**
     * Return a new model instance.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function newInstance(): Model
    {
        $modelName = $this->getModelName();

        return new $modelName;
    }

    /**
     * Create a new model from given parameters.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function newToken($expiresAt = null)
    {
        $newInstance = $this->newInstance();

        $newInstance->forceFill([
            'id' => hash('sha256', Str::orderedUuid()),
            'revoked' => false,
            'expires_at' => $this->parseExpiresAt($expiresAt),
        ]);

        return $newInstance;
    }

    /**
     * Parse and convert to the Carbon instance.
     *
     * @param  mixed  $expiresAt
     * @return \Illuminate\Support\Carbon
     */
    private function parseExpiresAt($expiresAt): Carbon
    {
        if ($expiresAt instanceof Carbon) {
            return $expiresAt;
        }

        return Carbon::parse($expiresAt ?: static::$defaultExpiresAt);
    }

    /**
     * Check if the token has been revoked.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $token
     * @return bool
     */
    public function isRevoked($token): bool
    {
        if (is_a($token, $this->getModelName())) {
            return $token->revoke;
        }

        return $this->newInstance()
            ->newQuery()
            ->whereKey($token)
            ->where('revoked', true)
            ->exists();
    }

    /**
     * Find a valid token record.
     *
     * @param  string  $id
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function findValid($id)
    {
        return $this->newInstance()
            ->newQuery()
            ->where('revoked', false)
            ->where('expires_at', '>', Carbon::now())
            ->find($id);
    }

    /**
     * Revoke the given token.
     *
     * @param  string  $id
     * @return bool
     */
    public function revoke($id)
    {
        return $this->newInstance()
            ->newQuery()
            ->whereKey($id)
            ->update(['revoked' => true]);
    }
}
