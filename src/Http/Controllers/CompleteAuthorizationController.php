<?php

namespace A2Workspace\SocialEntry\Http\Controllers;

use A2Workspace\SocialEntry\Exceptions\InvalidStateException;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;
use A2Workspace\SocialEntry\SocialEntry;

class CompleteAuthorizationController
{
    /**
     * 完成第三方平台授權
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback(Request $request, $provider)
    {
        $this->validateAuthorizationRequest($request, $provider);

        return $this->completeAuthorizationRequest($request, $provider);
    }

    /**
     * 確認請求的狀態有效
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $driver
     * @return void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function validateAuthorizationRequest(Request $request, $driver)
    {
        if (
            empty($request->session()->get('entry_continue')) ||
            $driver !== $request->session()->get('entry_provider')
        ) {
            throw new InvalidStateException;
        }
    }

    /**
     * 取出授權請求參數並清除 Session 相關的值
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function pullRequestParametersFromSession(Request $request)
    {
        $continueUri = $request->session()->get('entry_continue');
        $scopes = $request->session()->get('entry_scopes');

        $request->session()->forget([
            'entry_provider',
            'entry_continue',
            'entry_scopes',
        ]);

        return [$continueUri, $scopes];
    }

    /**
     * 完成第三方授權回傳，將使用者導回最終頁面並附上 auth_code
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $driver
     * @return \Illuminate\Http\Response
     */
    protected function completeAuthorizationRequest(Request $request, $driver)
    {
        list($continueUri, $scopes) = $this->pullRequestParametersFromSession($request);

        $provider = SocialEntry::provider($driver);

        if (($socialUser = $provider->user()) === null) {
            throw new InvalidStateException;
        }

        $authCode = SocialEntry::issueAuthCode(
            $socialUser->getId(),
            $socialUser->getProvider()->getDriverName(),
            $scopes
        );

        $payload = [
            'provider' => $provider->getDriverName(),
            'identifier' => $socialUser->getId(),
            'social_email' => $socialUser->getEmail(),
            'social_name' => $socialUser->getName(),
            'social_avatar' => $socialUser->getAvatar(),
            'auth_code_id' => $authCode->getKey(),
            'expire_time' => $authCode->expires_at->timestamp,
            'scopes' => $scopes,
        ];

        $code = Crypt::encrypt(json_encode($payload));

        $query = http_build_query(compact('code'));
        $redirectUri = http_build_url($continueUri, compact('query'), HTTP_URL_JOIN_QUERY);

        return redirect($redirectUri);
    }
}
