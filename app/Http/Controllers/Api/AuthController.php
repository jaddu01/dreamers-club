<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseBuilder;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email'=> 'required|email',
                'password'=>'required|min:8',
            ]);
            if($validator->fails()){
                return ResponseBuilder::error($validator->errors()->first(),$this->validationStatus);
            }

            $user = User::where('email',$request->email)->first();
            if(!$user){
                return ResponseBuilder::error("Invalid email address",$this->validationStatus);
            }
            if($user->status == 'inactive'){
                return ResponseBuilder::error("User is not active. Kindly contact to administrator.",$this->validationStatus);
            }
            if($user->otp_verified == 0){
                return ResponseBuilder::error("OTP not verified",$this->validationStatus);
            }

            $credentials = $request->only(['email','password']);
            if(!auth()->attempt($credentials)){
                return ResponseBuilder::error("Invalid Credential",$this->validationStatus);
            }

            $user = $request->user();

            $token = $user->createToken(config('app.name'),['app'])->accessToken;

            $this->response->user = new UserResource($user);

            return ResponseBuilder::successWithToken($token,$this->response);

        }catch (\Exception $e){
            Log::error($e);
            return ResponseBuilder::error(__('api.default_error_message'), $this->errorStatus);
        }
    }

    public function register(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'name'      => 'required|max:20',
                'email'     => 'required|max:50|email',
                'password'  => ['required','confirmed','required_with:password_confirmation', Password::min(8)],
            ]);
            if($validator->fails()){
                return ResponseBuilder::error($validator->errors()->first(),$this->validationStatus);
            }

            $user = User::where('email', $request->email)->first();
            $otp = 123456; //rand(100000,999999);
            if($user){
                //send otp
                $user->otp = $otp;
                $user->save();
                return ResponseBuilder::success(null,"OTP Sent successfully");
            }

            $input = $request->only(['name','email']);
            $input['password'] = Hash::make($request->password);
            $input['otp'] = $otp;
            $user = User::create($input);
            return ResponseBuilder::success(null,"OTP Sent successfully");


        }catch (\Exception $e){
            return $e;
            Log::error($e);
            // return ResponseBuilder::error(__('api.default_error_message'), $this->errorStatus);
        }
    }

    public function verifyOTP(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'email' => ['required', Rule::exists('users','email')->whereNull('deleted_at')],
                'otp'     => 'required|max:6',
            ]);
            if($validator->fails()){
                return ResponseBuilder::error($validator->errors()->first(),$this->validationStatus);
            }
            $user = User::where('otp', trim($request->otp))->where('email',$request->email)->first();
            if (!$user){
                return ResponseBuilder::error("OTP did not match. Try with correct OTP", $this->validationStatus);
            }

            $token = $user->createToken(config('app.name'),['app'])->accessToken;
            dd($token);

            $user->otp = "";
            $user->otp_verified = '1';
            $user->email_verified_at = date('Y-m-d h:i:s');
            $user->status = 'active';
            $user->save();

            $this->response->user = new UserResource($user);

            return ResponseBuilder::successWithToken($token,$this->response,"Logged in successfully");

        }catch (\Exception $e){
            Log::error($e);
            return $e;
            // return ResponseBuilder::error(__('api.default_error_message'), $this->errorStatus);
        }
    }
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function logout(Request $request)
    {
        $request->user('api')->token()->revoke();
        return ResponseBuilder::success(null,"Logged out successfully");
    }

    public function resendOTP(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email'     => ['required','email',Rule::exists('users','email')->whereNull('deleted_at')],
            ]);
            if($validator->fails()){
                return ResponseBuilder::error($validator->errors()->first(),$this->validationStatus);
            }
            $otp = 123456; //rand(100000,999999);
            $user = User::where('email',$request->email)->first();
            if (!$user){
                return ResponseBuilder::error("This email is not registered with us. Kindly sign up first.", $this->validationStatus);
            }

            $user->otp = $otp;
            $user->save();

            return ResponseBuilder::success(null,"OTP Sent successfully");
        }catch(Exception $e){
            Log::error($e);
            return ResponseBuilder::error(__('api.default_error_message'), $this->errorStatus);
        }
    }
}
