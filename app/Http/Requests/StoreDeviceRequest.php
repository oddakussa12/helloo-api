<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDeviceRequest extends FormRequest
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'deviceToken' => 'bail|nullable|size:64',
            'appShortVersion' => 'bail|required|string|between:1,64',
            'appVersion' => 'bail|required|string|between:1,64',
            'appBundleIdentifier' => 'bail|required|string|between:1,64',
            'vendorUUID' => 'bail|required|string|between:1,128',
            'systemName' => ['bail','required','string',Rule::in(['iOS', 'Android'])],
            'systemVersion' => 'bail|required|string|between:1,128',
            'phoneName' => 'bail|required|string|max:512',
            'phoneModel' => 'bail|required|string|between:1,128',
            'localizedModel' => 'bail|required|string|between:1,64',
            'networkType' => ['bail','required','string',Rule::in(['GPRS(2G)', 'Edge(2G)' , 'WCDMA(3G)' , 'HSDPA(3G)' , 'HSUPA(3G)' , 'CDMA1x(2G)' , 'CDMAEVDORev0(3G)' , 'CDMAEVDORevA(3G)' , 'CDMAEVDORevB(3G)' , 'HRPD(3G)' , 'LET(4G)'])],
            'carrierName' => 'bail|required|string|between:1,128',
            'devicePlatformName' => 'bail|required|string|between:1,128',
            'deviceType' => 'bail|required|numeric|between:1,2',
        ];
    }
}
