<?php

namespace A2Workspace\SocialEntry\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuthCodeRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'code' => 'required',
        ];
    }

    /**
     * Return the code value.
     */
    public function getAuthCode()
    {
        return $this->get('code');
    }
}
