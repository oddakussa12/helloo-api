<?php

namespace App\Http\Controllers\V1\Business;

use Illuminate\Http\Request;
use App\Models\Business\Recipient;
use App\Resources\AnonymousCollection;
use App\Http\Requests\RecipientRequest;
use App\Http\Controllers\V1\BaseController;

class RecipientController extends BaseController
{
    public function index(Request $request)
    {
        $userId = (int)$request->input('user_id' , 0);
        $auth = (int)auth()->id();
        if($userId!==$auth)
        {
            abort(403);
        }
        $recipients = Recipient::where('user_id' , $userId)->orderByDesc('updated_at')->get();
        return AnonymousCollection::collection($recipients);
    }

    public function store(RecipientRequest $request)
    {
        $auth = (int)auth()->id();
        $name = $request->input('name');
        $phone = $request->input('phone');
        $longitude = $request->input('longitude');
        $latitude = $request->input('latitude');
        $address = $request->input('address');
        $specific = $request->input('specific');
        $recipient = new Recipient();
        $recipient->id = app('snowflake')->id();
        $recipient->user_id = $auth;
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
        $auth = (int)auth()->id();
        $name = $request->input('name');
        $phone = $request->input('phone');
        $longitude = $request->input('longitude');
        $latitude = $request->input('latitude');
        $address = $request->input('address');
        $specific = $request->input('specific');
        $recipient = Recipient::where('id' , $id)->firstOrFail();
        if((int)$recipient->user_id!==$auth)
        {
            abort(403);
        }
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
        $auth = (int)auth()->id();
        $recipient = Recipient::where('id' , $id)->firstOrFail();
        if((int)$recipient->user_id!==$auth)
        {
            abort(403);
        }
        Recipient::where('id' , $id)->delete();
        return $this->response->noContent();
    }
}