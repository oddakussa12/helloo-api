<?php

namespace App\Http\Controllers\V1\Business;

use App\Models\Business\Recipient;
use App\Resources\AnonymousCollection;
use App\Http\Requests\RecipientRequest;
use App\Http\Controllers\V1\BaseController;

class RecipientController extends BaseController
{
    public function index()
    {
        $recipients = Recipient::orderByDesc('updated_at')->get();
        return AnonymousCollection::collection($recipients);
    }

    public function store(RecipientRequest $request)
    {
        $name = $request->input('name');
        $phone = $request->input('phone');
        $longitude = $request->input('longitude');
        $latitude = $request->input('latitude');
        $address = $request->input('address');
        $specific = $request->input('specific');
        $recipient = new Recipient();
        $recipient->name = $name;
        $recipient->phone = $phone;
        $recipient->longitude = $longitude;
        $recipient->latitude = $latitude;
        $recipient->address = $address;
        $recipient->specific = $specific;
        $recipient->save();
        return $this->response->created();
    }

    public function update(RecipientRequest $request , $id)
    {
        $name = $request->input('name');
        $phone = $request->input('phone');
        $longitude = $request->input('longitude');
        $latitude = $request->input('latitude');
        $address = $request->input('address');
        $specific = $request->input('specific');
        $recipient = Recipient::where('id' , $id)->firstOrFail();
        $recipient->name = $name;
        $recipient->phone = $phone;
        $recipient->longitude = $longitude;
        $recipient->latitude = $latitude;
        $recipient->address = $address;
        $recipient->specific = $specific;
        $recipient->save();
        return $this->response->accepted();
    }

    public function destroy($id)
    {
        Recipient::where('id' , $id)->delete();
        return $this->response->noContent();
    }
}