<h1 align="center">Laravel-Social-Entry</h1>
<p align="center">
<a href="https://github.com/A2Workspace/laravel-social-entry">
    <img alt="" src="https://github.com/A2Workspace/laravel-social-entry/actions/workflows/coverage.yml/badge.svg">
</a>
<a href="https://github.com/A2Workspace/laravel-social-entry">
    <img alt="" src="https://img.shields.io/github/workflow/status/A2Workspace/laravel-social-entry/tests?style=flat-square">
</a>
<a href="https://codecov.io/gh/A2Workspace/laravel-social-entry">
    <img alt="" src="https://img.shields.io/codecov/c/github/A2Workspace/laravel-social-entry.svg?style=flat-square">
</a>
<a href="https://github.com/A2Workspace/laravel-social-entry/blob/master/LICENSE">
    <img alt="" src="https://img.shields.io/github/license/A2Workspace/laravel-social-entry?style=flat-square">
</a>
<a href="https://packagist.org/packages/a2workspace/laravel-social-entry">
    <img alt="" src="https://img.shields.io/packagist/v/a2workspace/laravel-social-entry.svg?style=flat-square">
</a>
<a href="https://packagist.org/packages/a2workspace/laravel-social-entry">
    <img alt="" src="https://img.shields.io/packagist/dt/a2workspace/laravel-social-entry.svg?style=flat-square">
</a>
</p>

提供一個幾乎零配置，整合前後端的第三方登入的 API 身分認證接口

<br>

特性:

- 基於 [Laravel Socialite](https://github.com/laravel/socialite) 的 API 身分認證接口
- 專為前端 API 接口設計
- 解決一般登入與社群登入不統一的問題
- 解決新使用者透過社群登入後，要填寫完成註冊表格才能完成註冊的情況
- 整合社群帳號連結功能
- 支援多使用者模型
- 相容 **Nuxt.js**；參考套件 [nuxt-social-entry](https://github.com/A2Workspace/nuxt-social-entry)

快速前往:

- [# Installation | 安裝](#Installation-|-安裝)
- [# Configuration | 配置](#Configuration-|-配置)
  - [# 一個簡單的設定範例](#一個簡單的設定範例)
  - [# Registering Routes | 註冊路由](#Registering-Routes-|-註冊路由)

-----

## Installation | 安裝

要在專案中使用 SocialEntry，執行下列命令透過 Composer 引入到你的 Laravel 專案中:

```bash
composer require a2workspace/laravel-social-entry
```

接著使用 `vendor:publish` 命令生成設定檔:

```bash
php artisan vendor:publish --tag=@a2workspace/laravel-social-entry
```

現在你可以在 `config/social-entry.php` 中指定要啟用的第三方授權登入。

## Configuration | 配置

開始用 SocialEntry 前，如同使用 [Laravel Socialite](https://laravel.com/docs/9.x/socialite)，你必須要先將第三方服務設定加到 `config/services.php` 內。你可以使用下面的範例或參考說明 [Laravel Socialite Configuration](https://laravel.com/docs/9.x/socialite#configuration)。

### 一個簡單的設定範例:

```php
'github' => [
    'client_id' => env('GITHUB_CLIENT_ID'),
    'client_secret' => env('GITHUB_CLIENT_SECRET'),
    'redirect' => env('GITHUB_REDIRECT_URL', '/auth/github/callback'),
],

'facebook' => [
    'client_id' => env('FACEBOOK_CLIENT_ID'),
    'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
    'redirect' => env('FACEBOOK_REDIRECT_URL', '/auth/facebook/callback'),
],

'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URL', '/auth/google/callback'),
],

'line' => [
    'client_id' => env('LINE_CHANNEL_ID'),
    'client_secret' => env('LINE_SECRET'),
    'redirect' => env('LINE_REDIRECT_URL', '/auth/line/callback'),
],
```

`.env`/`.env.example` 設定:

```bash
# See https://github.com/settings/developers
GITHUB_CLIENT_ID=
GITHUB_CLIENT_SECRET=
GITHUB_REDIRECT_URL=

# See https://developers.facebook.com/apps/?show_reminder=true
FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
FACEBOOK_REDIRECT_URL=

# See https://console.cloud.google.com/apis/credentials
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URL=

# See https://developers.line.biz/console/
LINE_CHANNEL_ID=
LINE_SECRET=
LINE_REDIRECT_URL=
```

這裡提供第三方的登入設定頁面連結:

- `Github`: https://github.com/settings/developers
- `Facebook`: https://developers.facebook.com/apps/?show_reminder=true
- `Google`: https://console.cloud.google.com/apis/credentials
- `Line`: https://developers.line.biz/console/

### Registering Routes | 註冊路由

接著你應該在 `App\Providers\AuthServiceProvider` 的 `boot` 方法中，呼叫 `SocialEntry::routes` 方法來註冊路由。

```php
<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use A2Workspace\SocialEntry\SocialEntry;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        if (! $this->app->routesAreCached()) {
            SocialEntry::routes();
        }
    }
}

```
