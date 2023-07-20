<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Traits\CommonTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Otp;
use App\Models\UserPasswordReset;
use App\Models\UserPasswordDetails as UserPassword;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use App\Models\Userloginlog;

class ResetController extends Controller
{
    use CommonTrait;

    /***********************Change password function ******************************** */
    public function changePassword(Request $request)
    {
        try {
            $validatorArray = [
                // 'id'          => 'required',
                'oldpassword' => 'required',
                'password' => ['required', 'confirmed', Password::min(8), 'regex:/^(?=.*[a-z]){2,}(?=.*[A-Z])(?=.*\d)(?=.*(_|[^\w])).+$/'],

            ];
            //echo(Auth::user()->password);die;
            $messagesArray = [
                'password.regex' => 'Password must be of 8 characters Atleast 1 alphabets must be in upper case Atleast 1 letters must be in lower case Must be atleast 1 numeric.'
            ];
            $validator = Validator::make($request->all(), $validatorArray, $messagesArray);
            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }

            $currentPassword = UserPassword::where('user_id', Auth::user()->id)->orderBy('id', 'DESC')->first();
            if (Hash::check($request->oldpassword, $currentPassword->password)) {

                //date condtions
                $to = date('Y-m-d h:i:sa');
                $from = date("Y-m-d h:i:sa", strtotime("-6 months", strtotime(date('Y-m-d'))));

                $allPswds = UserPassword::where('user_id', Auth::user()->id)->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)->get();
                $allreadyUsed = false;

                //check each password
                foreach ($allPswds as $post) {
                    if (Hash::check($request->password, $post->password)) {
                        $allreadyUsed = true;
                        break;
                    }
                }

                //return if password already used within last 6 months
                if ($allreadyUsed) {
                    return $this->response('updateError', ['message' => 'already used password']);
                }

                //make all old passwords inactive
                UserPassword::where('user_id', Auth::user()->id)->update(['status' => 0]);

                //Create a new password
                $user = new UserPassword;
                $user->password = Hash::make($request->password);
                $user->user_id = Auth::user()->id;
                $user->status = '1';
                $user->expired_at = date("Y-m-d", strtotime("+1 months", strtotime(date('Y-m-d'))));
                $user->save();
                return $this->response('success', ['message' => 'Password updated successfully']);
            } else {
                return $this->response('updateError', ['message' => 'Password not updated']);
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }
    /***********************Send Otp*************************************************/
    public function sendOtp(Request $request)
    {
        try {
            $validatorArray = [
                'email' => 'required',
            ];
            $validator = Validator::make($request->all(), $validatorArray);
            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            if (is_numeric($request->email)) {
                $credentials['phone'] = $request->email;
            } else {
                $credentials['email'] = trim($request->email);
            }
            $chkUser = User::select(is_numeric($request->email) ? 'phone' : 'email')->where($credentials)->first();
            //dd( $chkUser );
            if (empty($chkUser)) {
                return $this->response('notvalid');
            }
            $genOtp = sprintf("%04d", mt_rand(1, 9999));
            // dd($genOtp);
            $otp = new Otp;
            $otp->otp = $genOtp;
            $otp->name = $request->email;
            $otp->status = 1;
            $otp->save();
            return $this->response('success', ['message' => 'Otp send successfully', 'otp' => $genOtp]);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }
    /***********************Verify Otp*********************************************/
    public function verifyOtp(Request $request)
    {
        try {
            $validatorArray = [
                'email' => 'required',
                'otp' => 'required',
            ];
            $validator = Validator::make($request->all(), $validatorArray);
            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            if (is_numeric($request->email)) {
                $credentials['phone'] = $request->email;
            } else {
                $credentials['username'] = trim($request->email);
            }

            $Otp = $request->otp;
            // echo($Otp);
            $geneOtp = Otp::select('id', 'name', 'otp', 'status', 'created_at')->where('name', $request->email)->orderBy('created_at', 'desc')->first();
            // echo($geneOtp->otp);die;
            $userdetails = User::select('id')->where($credentials)->first();
            $token = Auth::login($userdetails);
            if (!empty($geneOtp)) {
                if ($geneOtp->otp == $Otp) {
                    if($geneOtp->status == 1){
                        if (CommonTrait::is_expired($geneOtp->created_at, 5)) {
                            return $this->response('incorrectinfo', ['message' => 'otp expired']);
                        }
                        if($request->has('type') && $request->type == "reset"){
                            $otp = Otp::where('id', $geneOtp->id)->update(['status' => 2]);
                            $resetToken = md5(base64_encode(time().$userdetails->id.rand()));
                            $UserPasswordReset = new UserPasswordReset;
                            $UserPasswordReset->user_id = $userdetails->id;
                            $UserPasswordReset->token = $resetToken;
                            $UserPasswordReset->save();
                            $link = env('FRONT_URL').'auth/change-password?token='.$resetToken;
                            return $this->response('success', ['message' => 'Password reset link sent on your email.', 'link' => $link]);
                        }else{
                            $otp = Otp::where('id', $geneOtp->id)->update(['status' => 0]);
                        }
                        $location = $request->lat . "," . $request->lng;
                        Userloginlog::create(["userid" => Auth::user()->id, "ipaddress" => $request->ip(), 'latlng' => $location, 'device_name' => $request->server('HTTP_USER_AGENT')]);
                        $result = Auth::user();
                        $return['name']                 =   $result->fullname;
                        $return['userid']               =   $result->id;
                        $return['email']          	    =   $result->email;
                        $return['phone']           	    =   $result->phone;
                        $return['username']             =   $result->username;
                        $return['firmname']             =   $result->firmname;
                        $return['usertype']             =   $result->role;
                        $return['balance']              =   $result->balance;
                        $return['cd_balance']           =   $result->cd_balance;
                        $return['allowfundrequest']     =   $result->allowfundrequest; 
                        $return['is_onboard']           =   $result->is_onboard;
                        $return['is_kyc']               =   $result->is_kyc;
                        $return['is_email']             =   ($result->email == 1)?1:0;
                        $return['phone']                =   $result->phone;
                        $return['pannumber']            =   $result->pannumber; 
                        $return['is_staff']             =   $result->is_staff;
                        $return['bysignup']             =   $result->bysignup;
                        if($result->usertype == 0 && $result->is_staff ==0){
                            $return['show_usertype']        =   "SUPERADMIN";
                        }elseif($result->usertype ==0 && $result->is_staff==1){
                            $return['show_usertype']        =   "STAFF";
                        }elseif($result['usertype']==5){
                            $return['show_usertype']        =   "API PARTNER";
                        }elseif($result['usertype']==7){
                            $return['show_usertype']        =   "API SUPPORT";
                        }else{
                            $return['show_usertype']        =   "OTHER";
                        }

                        if(in_array($result->role,array(1,2,3,4,5,6))){
                            $result = Permission::where(array("userid" => Auth::user()->id))->first(); 

                           
                            //$result = $this->db->select('*')->from('permission')->where(['userid'=>$result['id']])->get()->row_array();
                            $return['permission'] = [];
                            if($result){
                                $return['permission'] = $result;
                            }else{
                                $return['permission'] = array();
                            }
                        }elseif(in_array($result->role,array(0))){
                            $return['permission'] = array("key"=>"All");
                            if($result->is_staff == 1)
                            {
                                // $return['staff_permissions'] = [];
                                // $ad_perm_array = $this->db->select('*')->from('staff_permissions')->where(['userid'=>$result['id']])->get()->row_array();
                                // unset($ad_perm_array['addeddate']);
                                // unset($ad_perm_array['updated_date']);
                                // $return['staff_permissions'] = $ad_perm_array;
                            }
                        }
                        return $this->response('success', [
                         'message' => 'Otp Match',
                         'data' =>  $return,
                         'authtoken' => $token,
                        ]);
                    }else{
                        return $this->response('incorrectinfo', ['message' => 'otp expired']);
                    }
                    
                } else {
                    return $this->response('incorrectinfo', ['message' => 'Invalid Otp!!']);
                }
            } else {
                return $this->response('incorrectinfo', ['message' => 'Invalid Details!!']);
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }
    /***********************Forgot Password****************************************/
    public function forgotPassword(Request $request)
    {
        try {
            $validatorArray = [
                'email' => 'required',
                'otp' => 'required',
                'password' => ['required', 'confirmed', Password::min(8), 'regex:/^(?=.*[a-z]){2,}(?=.*[A-Z])(?=.*\d)(?=.*(_|[^\w])).+$/'],
                'password' => ['required', 'confirmed', Password::min(8), 'regex:/^(?=.*[a-z]){2,}(?=.*[A-Z])(?=.*\d)(?=.*(_|[^\w])).+$/'],
                'password.regex' => 'Password must be of 8 characters Atleast 1 alphabets must be in upper case Atleast 1 letters must be in lower case Must be atleast 1 numeric.'
            ];
            $messagesArray = [
                'password.regex' => 'Password must be of 8 characters Atleast 1 alphabets must be in upper case Atleast 1 letters must be in lower case Must be atleast 1 numeric.'
            ];
            $validator = Validator::make($request->all(), $validatorArray, $messagesArray);
            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }

            $geneOtp = Otp::select('id')->where('name', $request->email)->where('otptype', 'reset')->where('otp', $request->otp)->where('status', 2)->orderBy('created_at', 'desc')->first();

            if (empty($geneOtp)) {
                return $this->response('incorrectinfo', ['message' => 'Invalid Details!!']);
            }

            $credentials = $request->only('password');

            if (is_numeric($request->email)) {
                $logintype = "phone";
                $credentials['phone'] = $request->email;
            } else {
                $logintype = "email";
                $cd['email'] = $credentials['email'] = trim($request->email);
            }
            $credentials['status'] = 1;

            $isValidEmail = User::where('status', 1)->when($logintype == "email", function ($query) use ($request) {
                $query->where('email', $request->email);
            })->when($logintype == "phone", function ($query) use ($request) {
                $query->where('phone', $request->email);
            })->first();
            if (!$isValidEmail) {
                return $this->response('notvalid');
            }

            $to = date('Y-m-d h:i:sa');
            $from = date("Y-m-d h:i:sa", strtotime("-6 months", strtotime(date('Y-m-d'))));

            $allPswds = UserPassword::where('user_id', $isValidEmail->id)->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)->get();
            $allreadyUsed = false;
            //check each password
            foreach ($allPswds as $post) {
                if (Hash::check($request->password, $post->password)) {
                    $allreadyUsed = true;
                    break;
                }
            }

            //return if password already used within last 6 months
            if ($allreadyUsed) {
                return $this->response('updateError', ['message' => 'already used password']);
            }

            //make all old passwords inactive
            UserPassword::where('user_id', $isValidEmail->id)->update(['status' => 0]);

            //Create a new password
            $user = new UserPassword;
            $user->password = Hash::make($request->password);
            $user->user_id = $isValidEmail->id;
            $user->status = '1';
            $user->expired_at = date("Y-m-d", strtotime("+1 months", strtotime(date('Y-m-d'))));
            $user->save();

            $otp = Otp::where('id', $geneOtp->id)->update(['status' => 1]);

            return $this->response('success', ['message' => 'Password reset successfully']);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }


    /*********************** Forgot Password Using mail link  ************************ */

    public function forgotPasswordMail(Request $request)
    {
        
        try {
            $validatorArray = [
                'token' => 'required',
                'password' => ['required', 'confirmed', Password::min(8), 'regex:/^(?=.*[a-z]){2,}(?=.*[A-Z])(?=.*\d)(?=.*(_|[^\w])).+$/'],
                'password' => ['required', 'confirmed', Password::min(8), 'regex:/^(?=.*[a-z]){2,}(?=.*[A-Z])(?=.*\d)(?=.*(_|[^\w])).+$/'],
                'password.regex' => 'Password must be of 8 characters Atleast 1 alphabets must be in upper case Atleast 1 letters must be in lower case Must be atleast 1 numeric.'
            ];
            $messagesArray = [
                'token.required' => 'Invalid token!',
                'password.regex' => 'Password must be of 8 characters Atleast 1 alphabets must be in upper case Atleast 1 letters must be in lower case Must be atleast 1 numeric.'
            ];

            $validator = Validator::make($request->all(), $validatorArray, $messagesArray);
            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            

            $UserPassword = UserPasswordReset::where('token', $request->token)->orderBy('id', 'desc')->first();
            if (empty($UserPassword)) {
                return $this->response('incorrectinfo', ['message' => 'Invalid token!!']);
            }
            if($UserPassword->status == 0){
                return $this->response('incorrectinfo', ['message' => 'Link Expired']);
            }

            

            $to = date('Y-m-d h:i:sa');
            $from = date("Y-m-d h:i:sa", strtotime("-6 months", strtotime(date('Y-m-d'))));

            $allPswds = UserPassword::where('user_id', $UserPassword->user_id)->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)->get();
            $allreadyUsed = false;
            //check each password
            foreach ($allPswds as $post) {
                if (Hash::check($request->password, $post->password)) {
                    $allreadyUsed = true;
                    break;
                }
            }

            //return if password already used within last 6 months
            if ($allreadyUsed) {
                return $this->response('updateError', ['message' => 'already used password']);
            }

            //make all old passwords inactive
            UserPassword::where('user_id', $UserPassword->user_id)->update(['status' => 0]);

            //Create a new password
            $user = new UserPassword;
            $user->password = Hash::make($request->password);
            $user->user_id = $UserPassword->user_id;
            $user->status = '1';
            $user->expired_at = date("Y-m-d", strtotime("+1 months", strtotime(date('Y-m-d'))));
            $user->save();

            $otp = UserPasswordReset::where('id', $UserPassword->id)->update(['status' => 0]);

            return $this->response('success', ['message' => 'Password reset successfully']);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    /*********************** User Send Otp *************************************************/
    public function usersendOtp(Request $request)
    {
        try {
            $validatorArray = [
                'phone' => 'required',
            ];
            $validator = Validator::make($request->all(), $validatorArray);
            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $credentials['phone'] = $request->phone;

            $chkUser = User::select(is_numeric($request->email) ? 'phone' : 'email')->where($credentials)->first();
            //dd( $chkUser );
            if (empty($chkUser)) {
                return $this->response('notvalid');
            }
            $genOtp = 9999; //sprintf("%04d", mt_rand(1, 9999));
            $otp = new Otp;
            $otp->otp = $genOtp;
            $otp->name = $request->phone;
            $otp->save();
            return $this->response('success', ['message' => 'Otp send successfully', 'otp' => $genOtp]);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    /*********************** User Verify Otp *********************************************/
    public function userverifyOtp(Request $request)
    {
        try {
            $validatorArray = [
                'phone' => 'required',
                'otp' => 'required',
            ];
            $validator = Validator::make($request->all(), $validatorArray);
            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $credentials['name'] = $request->phone;
            $usercredential['phone'] = $request->phone;
            $Otp = $request->otp;
            // echo($Otp);
            $geneOtp = Otp::select('id', 'name', 'otp', 'status', 'created_at')->where($credentials)->where('status', 0)->orderBy('created_at', 'desc')->first();
            $userdetails = User::select('id')->where($usercredential)->first();
            $token = Auth::login($userdetails);
            if (!empty($geneOtp)) {
                if ($geneOtp->otp == $Otp) {
                    Otp::where('id', $geneOtp->id)->update(['status' => 1]);
                    if (CommonTrait::is_expired($geneOtp->created_at, 5)) {
                        return $this->response('incorrectinfo', ['message' => 'otp expired']);
                    } else {
                        return $this->response('success', ['message' => 'Otp Match', 'authtoken' => $token, 'code' => '4']);
                    }
                } else {
                    return $this->response('incorrectinfo', ['message' => 'Invalid Otp!!']);
                }
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    /***********************Forgot Password****************************************/
    public function userforgotPassword(Request $request)
    {
        try {
            $validatorArray = [
                'phone' => 'required',
                'otp' => 'required',
                // 'password'  => ['required', 'confirmed', Password::min(8), 'regex:/^(?=.*[a-z]){2,}(?=.*[A-Z])(?=.*\d)(?=.*(_|[^\w])).+$/'],
                'password' => ['required', 'confirmed', Password::min(8), 'regex:/^(?=.*[a-z]){2,}(?=.*[A-Z])(?=.*\d)(?=.*(_|[^\w])).+$/'],
                'password.regex' => 'Password must be of 8 characters Atleast 1 alphabets must be in upper case Atleast 1 letters must be in lower case Must be atleast 1 numeric.'
            ];
            $messagesArray = [
                'password.regex' => 'Password must be of 8 characters Atleast 1 alphabets must be in upper case Atleast 1 letters must be in lower case Must be atleast 1 numeric.'
            ];
            $validator = Validator::make($request->all(), $validatorArray, $messagesArray);
            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $pwd = User::find(Auth::user()->id);
            $pwd->update(["password" => Hash::make($request->password)]);

            if ($pwd) {
                return $this->response('success', ['message' => 'Password updated successfully!']);
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }
}