<?php

namespace App\Http\Requests;

use Dingo\Api\Http\FormRequest;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;


class Request extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    protected function failedValidation(Validator $validator)
    {
        if ($this->container['request'] instanceof \Illuminate\Http\Request) {
            throw new ResourceException($validator->errors()->first(), $validator->errors());
        }

        throw (new ValidationException($validator))
            ->errorBag($this->errorBag)
            ->redirectTo($this->getRedirectUrl());
    }


}
