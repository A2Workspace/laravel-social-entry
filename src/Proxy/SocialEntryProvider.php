<?php

namespace A2Workspace\SocialEntry\Proxy;

use TypeError;
use Throwable;
use GuzzleHttp\Exception\ClientException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Contracts\Provider as ProviderContract;
use A2Workspace\SocialEntry\Exceptions\MissingConfigureException;

class SocialEntryProvider implements ProviderContract
{
    /**
     * The Socialite Provider driver name.
     *
     * @var string
     */
    private string $driverName;

    /**
     * The real Socialite Provider instance.
     *
     * @var \Laravel\Socialite\Contracts\Provider
     */
    private ProviderContract $provider;

    /**
     * Generate the real provider instance.
     *
     * @param  \Laravel\Socialite\Contracts\Provider  $provider
     * @param  string|null  $driverName
     */
    public function __construct(ProviderContract $provider, ?string $driverName = null)
    {
        $this->provider = $provider;
        $this->driverName = $driverName ?: strtolower(
            str_replace('Provider', '', class_basename($provider))
        );
    }

    /**
     * Return the driver name.
     *
     * @return string
     */
    public function getDriverName()
    {
        return $this->driverName;
    }

    /**
     * {@inheritDoc}
     */
    public function redirect()
    {
        return $this->provider->redirect();
    }

    /**
     * {@inheritDoc}
     *
     * @return \A2Workspace\SocialEntry\Proxy\SocialUser|null
     */
    public function user(): ?SocialUser
    {
        try {
            return new SocialUser($this->provider->user(), $this);
        } catch (ClientException $e) {
            return null;
        } catch (TypeError $e) {
            return null;
        }
    }

    /**
     * Return the wrapped Socialite Provider from the given name.
     *
     * @param  string  $driver
     * @param  boolean  $alwaysStateless
     * @return self
     */
    public static function providerFor(string $driver, bool $alwaysStateless = true): self
    {
        try {
            $provider = Socialite::driver($driver);
        }

        // 捕獲並處理 config/services.php 未設定好服務配置時的情形
        // 請參考 README.md 或 https://laravel.com/docs/9.x/socialite#configuration
        catch (Throwable $error) {
            if (pathinfo($error->getFile(), PATHINFO_FILENAME) === 'SocialiteManager') {
                throw new MissingConfigureException($driver);
            }

            throw $error;
        }

        // // 若可能的話將第三方登入請求轉換為無狀態
        // if ($alwaysStateless && method_exists($provider, 'stateless')) {
        //     $provider->{'stateless'}();
        // }

        return new self($provider, $driver);
    }
}
