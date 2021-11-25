<?php

namespace App\Http\Controllers\V1\Business;

use App\Models\CountryCode;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\Http\Controllers\V1\BaseController;

class CountryCodeController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $countryCode = CountryCode::select('name','code','areaCode','icon')->get();
        return $countryCode;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', 'https://qneventsource.mmantou.cn/country_phone_code.json');
        $data = (string) $response->getBody();
        $response = json_decode($data, true);
        foreach($response['data'] as $element) {
            $countryCode = new CountryCode();

            $countryCode->name = $element['name'];
            $countryCode->code = $element['code'];
            $countryCode->areacode = $element['areaCode'];
            $countryCode->icon = $element['icon'];
            $countryCode->save();
        }
        return "Success";
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CountryCode  $countryCode
     * @return \Illuminate\Http\Response
     */
    public function show(CountryCode $countryCode)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CountryCode  $countryCode
     * @return \Illuminate\Http\Response
     */
    public function edit(CountryCode $countryCode)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CountryCode  $countryCode
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CountryCode $countryCode)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CountryCode  $countryCode
     * @return \Illuminate\Http\Response
     */
    public function destroy(CountryCode $countryCode)
    {
        //
    }
}
