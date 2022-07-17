<?php

namespace A2Workspace\SocialEntry;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use A2Workspace\SocialEntry\Proxy\SocialEntryProvider;
use A2Workspace\SocialEntry\Entity\AuthCode;
use A2Workspace\SocialEntry\Entity\AuthCodeRepository;
use A2Workspace\SocialEntry\Entity\AccessToken;
use A2Workspace\SocialEntry\Entity\AccessTokenRepository;
use A2Workspace\SocialEntry\Entity\IdentifierRepository;
use A2Workspace\SocialEntry\Exceptions\InvalidUserModelException;

class SocialEntry
{
    /**
     * The name
     *
     * @var string
     */
    protected static $userModel;

    /**
     * Binds the routes.
     *
     * @param  callable|array  $handler
     * @return void
     */
    public static function routes($handler = [])
    {
        $registrar = app(RouteRegistrar::class);

        if (is_callable($handler)) {
            $handler($registrar);
        } else {
            $handler = is_array($handler) ? $handler : [$handler];
            $registrar->all($handler);
        }
    }

    /**
     * Return the AuthCodeRepository instance.
     *
     * @return \A2Workspace\SocialEntry\Entity\AuthCodeRepository
     */
    public static function authCodes(): AuthCodeRepository
    {
        return app(AuthCodeRepository::class);
    }

    /**
     * Return the AccessTokenRepository instance.
     *
     * @return \A2Workspace\SocialEntry\Entity\AccessTokenRepository
     */
    public static function accessTokens(): AccessTokenRepository
    {
        return app(AccessTokenRepository::class);
    }

    /**
     * Return the IdentifierRepository instance.
     *
     * @return \A2Workspace\SocialEntry\Entity\IdentifierRepository
     */
    public static function identifiers(): IdentifierRepository
    {
        return app(IdentifierRepository::class);
    }

    /**
     * Return the wrapped Socialite Provider from the given name.
     *
     * @param  string  $driver
     * @param  bool  $alwaysStateless
     * @return \A2Workspace\SocialEntry\Proxy\SocialEntryProvider
     */
    public static function provider(string $driver, bool $alwaysStateless = true): SocialEntryProvider
    {
        return SocialEntryProvider::providerFor($driver, $alwaysStateless);
    }

    /**
     * 透過給定的參數發布一個新 AuthCode
     *
     * @param  string  $identifier
     * @param  string  $provider
     * @param  mixed  $scopes
     * @param  \Illuminate\Support\Carbon|string|null  $expiresAt
     * @return \A2Workspace\SocialEntry\Entity\AuthCode
     */
    public static function issueAuthCode($identifier, $provider, $scopes = null, $expiresAt = null): AuthCode
    {
        $authCode = static::authCodes()->newAuthCode($expiresAt);

        $attributes = [
            'identifier' => $identifier,
            'provider' => $provider,
            'scopes' => $scopes,
        ];

        $authCode->forceFill($attributes)->save();

        return $authCode;
    }

    /**
     * 透過給定的參數發布一個新 AccessToken
     *
     * @param  string  $identifier
     * @param  string  $provider
     * @param  mixed  $scopes
     * @param  \Illuminate\Support\Carbon|string|null  $expiresAt
     * @return \A2Workspace\SocialEntry\AccessToken
     */
    public static function issueAccessToken($identifier, $provider, $scopes = null, $expiresAt = null): AccessToken
    {
        $accessToken = static::accessTokens()->newToken($expiresAt);

        $attributes = [
            'identifier' => $identifier,
            'provider' => $provider,
            'scopes' => $scopes,
        ];

        $accessToken->forceFill($attributes)->save();

        return $accessToken;
    }

    // =========================================================================
    // = Local User
    // =========================================================================

    /**
     * Set the user model class name.
     *
     * @param  string  $userModel
     * @return void
     *
     * @throws \A2Workspace\SocialEntry\Exceptions\InvalidUserModelException
     */
    public static function useUserModel($userModel)
    {
        if (
            is_null($userModel) ||
            (class_exists($userModel) && is_subclass_of($userModel, Model::class))
        ) {
            static::$userModel = $userModel;
            return;
        }

        throw new InvalidUserModelException(
            sprintf(
                'The user model must instance of %s. %s given',
                Model::class,
                $userModel,
            )
        );
    }

    /**
     * Get the user model class name.
     *
     * @return string
     *
     * @throws \A2Workspace\SocialEntry\Exceptions\InvalidUserModelException
     */
    public static function userModel()
    {
        if (static::$userModel) {
            return static::$userModel;
        }

        // Here we try to get the model name of current auth provider settings.
        $driverName = Auth::getDefaultDriver();
        $providerName = config("auth.guards.{$driverName}.provider");
        $modelName = config("auth.providers.{$providerName}.model");

        if ($modelName && class_exists($modelName)) {
            return $modelName;
        }

        throw new InvalidUserModelException('Cannot resolve default user model');
    }

    /**
     * 透過給定的條件搜尋本地使用者
     *
     * @param  string  $identifier
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Contracts\Auth\Authenticatable|null
     */
    public static function findLocalUser($identifier, $type)
    {
        $userModel = static::userModel();

        if (method_exists($userModel, 'findForSocialite')) {
            return (new $userModel)->findForSocialite($identifier, $type);
        }

        return static::identifiers()->findLocalUser(
            $identifier,
            $type,
            $userModel
        );
    }
}
