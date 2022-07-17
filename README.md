# A2Workspace/Laravel-Social-Entry

提供一個基於 Laravel Socialite 的 API 身分認證接口"

## Install

```bash
composer config repositories.a2workspace/laravel-social-entry path ./packages/laravel-social-entry
composer require "a2workspace/laravel-social-entry:*"
```

## Configuration

`config/services.php`

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

`.env`
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
