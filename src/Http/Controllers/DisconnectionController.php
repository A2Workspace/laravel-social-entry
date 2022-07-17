<?php

namespace A2Workspace\SocialEntry\Http\Controllers;

use Illuminate\Http\Request;
use A2Workspace\SocialEntry\SocialEntry;
use A2Workspace\SocialEntry\Entity\Identifier;
use A2Workspace\SocialEntry\Concerns\ValidatesUserModels;

class DisconnectionController
{
    use ValidatesUserModels;

    /**
     * 解除第三方社群帳號綁定
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $this->validateDisconnectionRequest($request);

        $this->assertValidUserAuthorized($request);

        $identifier = $this->findIdentifierFromRequest($request);

        if (empty($identifier)) {
            return $this->respondNothingToDo($request);
        }

        $identifier->delete();

        return $this->respondSuccessful($request);
    }

    /**
     * 驗證請求
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function validateDisconnectionRequest(Request $request)
    {
        $request->validate([
            'type' => 'required',
            'identifier' => 'required',

            // ...
        ]);
    }

    /**
     * 透過給定 request 搜尋符合的 Identifier 模型
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \A2Workspace\SocialEntry\Entity\Identifier|null
     */
    protected function findIdentifierFromRequest(Request $request): ?Identifier
    {
        $user = $request->user();

        return SocialEntry::identifiers()
            ->queryIdentifiers(
                $request->identifier,
                $request->type,
                get_class($user),
            )
            ->where('user_id', $user->getKey())
            ->first();
    }

    /**
     * 回傳未執行任何動作
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    protected function respondNothingToDo(Request $request)
    {
        return response([
            'status' => false,
            'message' => '已解除綁定',
        ]);
    }

    /**
     * 回傳已成功執行
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    protected function respondSuccessful(Request $request)
    {
        return response([
            'status' => true,
            'message' => '已解除綁定',
        ]);
    }
}
