<?php

namespace A2Workspace\SocialEntry\Concerns;

use Illuminate\Http\Request;
use A2Workspace\SocialEntry\SocialEntry;
use A2Workspace\SocialEntry\Exceptions\UnexpectedUserAuthorization;

trait ValidatesUserModels
{
    /**
     * 確定登入的使用者符合當前處理的使用者類型
     *
     * @param  \Illuminate\Http\Request|null  $request
     * @return void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \A2Workspace\SocialEntry\Exceptions\UnexpectedUserAuthorization
     */
    protected function assertValidUserAuthorized(?Request $request = null): void
    {
        $user = ($request ?: request())->user();

        if (empty($user)) {
            abort(401);
        }

        if (! is_a($user, SocialEntry::userModel())) {
            throw new UnexpectedUserAuthorization;
        }
    }
}
