<?php

namespace A2Workspace\SocialEntry\Http\Controllers;

use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use A2Workspace\SocialEntry\SocialEntry;

class AuthorizationController
{
    /**
     * @var string
     */
    protected $redirectUriName = 'continue';

    /**
     * 處理重導到第三方平台授權畫面
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function authorize(Request $request): RedirectResponse
    {
        $this->validateAuthorizationRequest($request);

        return $this->redirectForAuthorization(
            $request,
            $request->provider,
            $request->{$this->redirectUriName},
        );
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function validateAuthorizationRequest(Request $request)
    {
        $request->validate([
            $this->redirectUriName => 'required|url',
            'provider' => [
                'required',
                Rule::in(Arr::wrap(config('social-entry.providers'))),
            ],
        ]);
    }

    /**
     * 將使用者重導至第三方登入授權頁面。
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $driver
     * @param  string  $continueUri
     * @return \Illuminate\Http\RedirectResponse
     */
    private function redirectForAuthorization(Request $request, $driver, $continueUri)
    {
        $provider = SocialEntry::provider($driver);

        $request->session()->put('entry_continue', $continueUri);
        $request->session()->put('entry_provider', $provider->getDriverName());

        // 這行留著方便測試
        // dd($provider->redirect()->getTargetUrl());

        return $provider->redirect();
    }
}
