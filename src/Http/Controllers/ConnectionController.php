<?php

namespace A2Workspace\SocialEntry\Http\Controllers;

use Illuminate\Http\Request;
use A2Workspace\SocialEntry\SocialEntry;
use A2Workspace\SocialEntry\AccessTokenPayload;
use A2Workspace\SocialEntry\Concerns\ValidatesUserModels;

class ConnectionController extends AbstractGrantController
{
    use ValidatesUserModels;

    /**
     * {@inheritDoc}
     */
    protected function completeRequestFromToken(Request $request, AccessTokenPayload $accessTokenPayload)
    {
        $this->assertValidUserAuthorized($request);

        // 若該社群帳號已被綁定，則提前中止
        if ($this->hasBeenConnected($accessTokenPayload)) {
            return $this->respondAlreadyConnected($request, $accessTokenPayload);
        }

        $identifier = SocialEntry::identifiers()->newIdentifierFor(
            $request->user(),
            $accessTokenPayload->identifier,
            $accessTokenPayload->provider,
        );

        $identifier->save();

        return $this->respondHasBeenConnecnted($request, $accessTokenPayload);
    }



    /**
     * 檢查社群帳號是否已經被綁定
     *
     * @param  \A2Workspace\SocialEntry\AccessTokenPayload  $accessTokenPayload
     * @return bool
     */
    protected function hasBeenConnected(AccessTokenPayload $accessTokenPayload): bool
    {
        return SocialEntry::identifiers()->exists(
            $accessTokenPayload->identifier,
            $accessTokenPayload->provider,
            $accessTokenPayload->local_user_type,
        );
    }

    /**
     * 回傳該社群帳號已被綁定
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \A2Workspace\SocialEntry\AccessTokenPayload  $accessTokenPayload
     * @return \Illuminate\Http\Response
     */
    protected function respondAlreadyConnected(Request $request, AccessTokenPayload $accessTokenPayload)
    {
        return response([
            'status' => false,
            'message' => '該帳號已被綁定',
        ]);
    }

    /**
     * 回傳成功綁定了社群帳號
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \A2Workspace\SocialEntry\AccessTokenPayload  $accessTokenPayload
     * @return \Illuminate\Http\Response
     */
    protected function respondHasBeenConnecnted(Request $request, AccessTokenPayload $accessTokenPayload)
    {
        return response([
            'status' => true,
            'message' => '已成功綁定',
        ]);
    }
}
