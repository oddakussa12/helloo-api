<?php

namespace App\Foundation\Auth\Passwords;

use App\Rules\UserPhone;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Auth\RedirectsUsers;

trait ResetsPasswords
{
    use RedirectsUsers;

    /**
     * Display the password reset view for the given token.
     *
     * If no token is present, display the link request form.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|null  $token
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showResetForm(Request $request, $token = null)
    {
        return view('auth.passwords.reset')->with(
            ['token' => $token, 'email' => $request->email]
        );
    }

    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */

    public function resetByPhone(Request $request)
    {
        $user_phone = $request->input('user_phone' , "");
        $user_phone_country = $request->input('user_phone_country' , "86");
        $code = $request->input('code');
        $password = $request->input('password');
        $password_confirmation = $request->input('password_confirmation');
        $rules = [
            'code' => 'bail|required',
            'user_phone' => [
                'bail',
                'required',
                new UserPhone()
            ],
            'password' => 'bail|required|confirmed|min:4|max:16',
            'password_confirmation' => 'bail|required|same:password',
        ];
        $validationField = array(
            'code' => $code,
            'user_phone'=>$user_phone_country.$user_phone,
            'password'=>$password,
            'password_confirmation'=>$password_confirmation,
        );
        \Validator::make($validationField, $rules)->validate();
        $user = \DB::table('users_phones')->where('user_phone_country', $user_phone_country)->where('user_phone', $user_phone)->first();
        if(blank($user))
        {
            return 'passwords.phone';
        }
        $phone = \DB::table('phone_password_resets')->where('phone_country', $user_phone_country)->where('phone', $user_phone)->first();
        if(blank($phone)||$code!==strval($phone->code))
        {
            return 'passwords.code';
        }
        \DB::table('users')->where('user_id' , $user->user_id)->update(array(
            'user_pwd'=>bcrypt($password)
        ));
        \DB::table('phone_password_resets')->where('phone_country', $user_phone_country)->where('phone', $user_phone)->delete();
        return true;
    }

    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function reset(Request $request)
    {

        $this->validate($request, $this->rules(), $this->validationErrorMessages());

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $response = $this->broker()->reset(
            $this->credential($request), function ($user, $password) {
                $this->resetPassword($user, $password);
            }
        );
        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return $response == Password::PASSWORD_RESET
                    ? true
                    : $response;
    }

    /**
     * Get the password reset validation rules.
     *
     * @return array
     */
    protected function rules()
    {
        return [
            'token' => 'required_without:code',
            'code' => 'required_without:token',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:4|max:16',
        ];
    }

    /**
     * Get the password reset validation error messages.
     *
     * @return array
     */
    protected function validationErrorMessages()
    {
        return [];
    }

    /**
     * Get the password reset credentials from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credential(Request $request)
    {
        $params = $request->only(
            'email', 'password', 'password_confirmation', 'token' , 'code'
        );
        $credential = array('user_email'=>$params['email'],'password'=>$params['password'],'password_confirmation'=>$params['password_confirmation']);
        isset($params['token'])&&$credential['token'] = $params['token'];
        isset($params['code'])&&$credential['code'] = $params['code'];
        return $credential;
    }

    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @param  string  $password
     * @return bool
     */
    protected function resetPassword($user, $password)
    {
        $user->setUserPwdAttribute($password);

        $user->save();

//        event(new PasswordReset($user));
        return true;
//        $this->guard()->login($user);
    }

    /**
     * Get the response for a successful password reset.
     *
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function sendResetResponse($response)
    {
        return redirect($this->redirectPath())
                            ->with('status', trans($response));
    }

    /**
     * Get the response for a failed password reset.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function sendResetFailedResponse(Request $request, $response)
    {
        return redirect()->back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => trans($response)]);
    }

    /**
     * Get the broker to be used during password reset.
     *
     * @return \Illuminate\Contracts\Auth\PasswordBroker
     */
    public function broker()
    {
        return Password::broker();
    }

    /**
     * Get the guard to be used during password reset.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard();
    }
}
