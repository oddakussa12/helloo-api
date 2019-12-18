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
            'deviceToken' => 'bail|nullable|string|between:1,128',
            'appShortVersion' => 'bail|nullable|string|between:1,64',
            'appVersion' => 'bail|nullable|string|between:1,64',
            'appBundleIdentifier' => 'bail|nullable|string|between:1,64',
            'vendorUUID' => 'bail|nullable|string|between:1,128',
            'registrationId' => 'bail|required_if:deviceType,1|string|between:1,64',
            'systemName' => ['bail','nullable','string',Rule::in(['iOS', 'Android'])],
            'systemVersion' => 'bail|nullable|string|between:1,128',
            'phoneName' => 'bail|nullable|string|max:512',
            'phoneModel' => 'bail|nullable|string|between:1,128',
            'localizedModel' => 'bail|nullable|string|between:1,64',
            'networkType' => ['bail','nullable','string',Rule::in(['GPRS(2G)', 'Edge(2G)' , 'WCDMA(3G)' , 'HSDPA(3G)' , 'HSUPA(3G)' , 'CDMA1x(2G)' , 'CDMAEVDORev0(3G)' , 'CDMAEVDORevA(3G)' , 'CDMAEVDORevB(3G)' , 'HRPD(3G)' , 'LET(4G)'])],
            'carrierName' => 'bail|nullable|string|between:1,128',
            'devicePlatformName' => 'bail|nullable|string|between:1,128',
            'deviceType' => 'bail|required|numeric|between:1,2',
            'deviceLanguage' => 'bail|required|string|between:1,16',
        ];
    }
}
