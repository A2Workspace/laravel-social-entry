<?php

namespace A2Workspace\SocialEntry\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AccessTokenRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'access_token' => 'required',
        ];
    }

    /**
     * Return the code value.
     */
    public function getAccessToken()
    {
        return $this->get('access_token');
    }
}
