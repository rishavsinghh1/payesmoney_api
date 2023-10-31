<?php
namespace App\Http\Controllers\Dmt; 
use App\Http\Controllers\Controller;
use App\Http\Traits\CommonTrait; 
use App\Models\User;
use App\Models\frm;
use App\Models\gststate;
use App\Models\Depositor;
use App\Models\Remitterauth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Traits\HeaderTrait;
use App\Http\Traits\ChargesTrait; 
use App\Libraries\Psdmt; 
use Illuminate\Support\Facades\Auth;
use App\Libraries\Whatsapplib;
class RemitterController extends Controller
{
    use CommonTrait,HeaderTrait,ChargesTrait;
    public function __construct(){
        $this->status = ['0'=>'Deactive','1'=>'Active'];
    } 

    public function getremitter(Request $request)
    {
        try {
            $validated = Validator::make($request->all(), [  
                "mobile"      => 'required|digits:10|numeric',  
            ]);
            if ($validated->fails()) {
                $message   = $this->validationResponse($validated->errors());
                return $this->response('validatorerrors', $message);
            }
            $userdata = Auth::user(); 
            if ($userdata && in_array($userdata->role, array(5))) {
                $mobile =$request->mobile;
                $frm    = frm::select("*")->where("status",1)->where("frm_details",$mobile)->first();  
                if(empty($frm)){
                    $getDetailRetailer = Psdmt::doqueryremitter(array("mobile" => $mobile));  
                    //dd($getDetailRetailer);
                    if($getDetailRetailer['response_code']==1 && $getDetailRetailer['status'] == true){
                        $getcustomer = Depositor::select("*")->where("mobile",$mobile)->first(); 
                        if (!empty($getcustomer)) {
                            $ps_limit    =   $getDetailRetailer['DMT1_limit'];
                            $ps_limit_2    =   $getDetailRetailer['DMT2_limit'];
                            $ps_limit_3    =   $getDetailRetailer['DMT3_limit']; 
                            if($ps_limit < $getcustomer['limit'] || $ps_limit_2 < $getcustomer['limit_2']  || $ps_limit_3 < $getcustomer['limit_3']){
                                $limit  =    $ps_limit;
                                $limit2  =   $ps_limit_2;
                                $limit3  =   $ps_limit_3;
                                if($limit > 0){
                                    $isupdate = Depositor::where("mobile",$mobile)->update(array("fname"=>$getDetailRetailer['fname'],"lname"=>$getDetailRetailer['lname'],"limit" => $limit
                                    ,"limit_2" => $limit2,"limit_3" => $limit3));
                                }
                            }else{
                                $isupdate = Depositor::where("mobile",$mobile)->update(array("fname"=>$getDetailRetailer['fname'],"lname"=>$getDetailRetailer['lname']));
                                $limit  =   $getcustomer['limit'];
                                $limit2  =   $getcustomer['limit_2'];
                                $limit3  =   $getcustomer['limit_3'];
                            }
                                $response = [
                                    'message' => "Remitter account details fetched",
                                    'fname'=> $getDetailRetailer['fname'],
                                    'lname'=> $getDetailRetailer['lname'],
                                    'DMT1_limit'=>$getDetailRetailer['DMT1_limit'],
                                    'DMT2_limit'=> $getDetailRetailer['DMT2_limit'],
                                    'DMT3_limit'=>$getDetailRetailer['DMT3_limit'],
                                    'mobile'=>$request->mobile,
                                    'statuscode'=>200
                                ];
                            return $this->response('success', $response);  
                        } else {
                            $mpin= rand(1111,9999);
                            $min = strtotime("47 years ago");
                            $max = strtotime("18 years ago");
                            $rand_time = mt_rand($min, $max);
                            $birth_date = date('Y-m-d', $rand_time);
                           
                            $reqData   =  array( 
                                "mempin"    =>  $mpin,
                                "creator"   =>  $userdata->id,
                                "fname"     =>  $getDetailRetailer['fname'],
                                "lname"     =>  $getDetailRetailer['lname'],
                                "dob"       =>  $birth_date, 
                                "mobile"    =>  $mobile, 
                                "address"   =>  'Noida',
                                "status"    =>  1,
                                "limit"     =>  $getDetailRetailer['DMT1_limit'],
                                "limit_2"     =>  $getDetailRetailer['DMT2_limit'],
                                "limit_3"     =>  $getDetailRetailer['DMT3_limit']
                            );  
                            $insertdata = Depositor::insertGetId($reqData); 
                            if($insertdata){
                                $d=[
                                    'api_token'=>'94d83070-4097-4409-938d-5b9583d037f4',
                                    'mobile'=>'91'.$mobile,
                                    'message'=> urlencode("Dear " . $userdata->fullname . " You have been successfully registered with Payesmoney.Your MPIN is " . $reqData['mempin'] ."    Powered by Payesmoney")
                                ];
                                $data=  Whatsapplib::doSentMessage($d);
                                //$this->sendotpmpin(array("userid"=>$this->valid_user['userid'],"mobile"=>$mobile,"name"=>$reqData['fname'],"pin"=>$this->pin,"template"=>"remitterPin"));
                                        
                                $response = [
                                    'message' => "Remitter account created successfully !",
                                    'fname'=> $getDetailRetailer['fname'],
                                    'lname'=> $getDetailRetailer['lname'],
                                    'DMT1_limit'=>$getDetailRetailer['DMT1_limit'],
                                    'DMT2_limit'=> $getDetailRetailer['DMT2_limit'],
                                    'DMT3_limit'=>$getDetailRetailer['DMT3_limit'],
                                    'mobile'=>$request->mobile,
                                    'statuscode'=>201
                                ];
                            return $this->response('success', $response); 
                            }
                        }
                    }elseif($getDetailRetailer['status'] == true && $getDetailRetailer['response_code'] == 0 and isset($getDetailRetailer['stateresp'])){
                        $response = [
                            'message' => "An OTP has been sent to remitter mobile number.",
                            'remrecord'=> 0, 
                            'mobile'   =>$mobile, 
                            'statuscode'=>203,
                            'stateresp'=> $getDetailRetailer['stateresp'], 
                        ]; 
                        return $this->response('success', $response);
                    }else{
                        $response = [
                            'errors' => "invalid!",
                            'message' => $getDetailRetailer['message']
                        ];
                        return $this->response('notvalid', $response);  
                    }  
                }else{
                    $response = [
                        'errors' => "invalid!",
                        'message' => "This mobile number is not authorised to avail DMT transaction facility"
                    ];
                    return $this->response('notvalid', $response); 
                }  
            }else { 
                $response = [
                    'errors' => "invalid!",
                    'message' => "Not Authorised!!"
                ];
                return $this->response('notvalid', $response); 
            } 
            
        }catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function registerremitter(Request $request)
    {
        try {
            $validated = Validator::make($request->all(), [  
                "mobile"        => 'required|digits:10|numeric', 
                'firstname'     =>  'required|min:2|max:30',
                'lastname'      =>  'required|min:2|max:30',
                'dob'           =>  ['required', 'date'],
                'pincode'       =>  'string|required',
                'otp'           =>  'string|required',
                'stateresp'     =>  'string|required'  
            ]);
            if ($validated->fails()) {
                $message   = $this->validationResponse($validated->errors());
                return $this->response('validatorerrors', $message);
            }
            $userdata = Auth::user(); 
            if ($userdata && in_array($userdata->role, array(5))) {
                $mobile     =   $request->mobile;
                $pinc       =   $request->pincode;
                $otp        =   $request->otp; 
                $frm    = frm::select("*")->where("status",1)->where("frm_details",$mobile)->first();  
                if(empty($frm)){
                    $remData    =   array (
                        'mobile'    =>  $mobile,
                        'firstname' =>  strtoupper($request->firstname),
                        'lastname'  =>  strtoupper($request->lastname), 
                        'otp'       =>  $request->otp,
                        'pincode'   =>  $pinc,
                        'stateresp' =>  $request->stateresp, 
                        'dob'       =>  $request->dob
                    );
                    $getresult = Psdmt::registerremitter($remData);   
                    if(isset($getresult) && $getresult['statuscode'] == 1){
                        $getcustomer = Depositor::select('*')->where('mobile',$mobile)->first();
                        if(empty($getcustomer)){
                            $reqData = array( 
                                "mempin"    =>  $this->pin,
                                "creator"   =>  $service['userid']['user']['id'],
                                "fname"     =>  strtoupper($request->firstname),
                                "lname"     =>  strtoupper($request->lastname),
                                "mobile"    =>  $mobile,
                                "dob"       =>  date('Y-m-d',strtotime($request->dob)),
                                "gender"    =>  "Male",
                                "address"   => $request->pincode,
                                "limit"     =>  25000,
                                "status"    =>  1,
                            );
                    // $this->sendotpmpin(array("userid"=>$this->valid_user['userid'],"mobile"=>$reqData['mobile'],"name"=>$reqData['fname'],"pin"=>$this->pin,"template"=>"remitterPin"));
                        $getcustomer = Depositor::insertGetId($reqData); 
                        }else{
                            $reqData['fname']   =   $getcustomer['fname'];
                            $reqData['lname']   =   $getcustomer['lname'];
                            $reqData['limit']   =   $getcustomer['limit'];
                            $reqData['mobile']  =   $getcustomer['mobile'];
                        }

                        $this->response['statuscode']   =     200;
                        $this->response['status']        =     true; 
                        $this->response['message']      =     "Remitter successfully registered.";
                    }else{
                        $response = [
                            'statuscode' => "invalid!",
                            'message' => $getresult['message']
                        ];
                        return $this->response('notvalid', $response);  
                    }
                }else{
                    $response = [
                        'errors' => "invalid!",
                        'message' => "This mobile number is not authorised to avail DMT transaction facility"
                    ];
                    return $this->response('notvalid', $response); 
                } 
            }else { 
                $response = [
                    'errors' => "invalid!",
                    'message' => "Not Authorised!!"
                ];
                return $this->response('notvalid', $response); 
            } 
            
        }catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }
    
    public function remitterotp(Request $request)
    {
        try {
            $validated = Validator::make($request->all(), [  
                "mobile"      => 'required|digits:10|numeric',  
            ]);
            if ($validated->fails()) {
                $message   = $this->validationResponse($validated->errors());
                return $this->response('validatorerrors', $message);
            }
            $userdata = Auth::user(); 
            if ($userdata && in_array($userdata->role, array(5))) {
                $mobile =$request->mobile;
                $getcustomer = Depositor::select("*")->where("mobile",$mobile)->first(); 
                if(!empty($getcustomer)){

                    $response = [
                        'response' => 1, 
                        'statuscode' => 200,
                        'status' => true,
                        'message' => "OTP sent on remitter mobile."
                    ];
                    return $this->response('success', $response);  
                }else{
                    $response = [
                        'response' => 2001,
                        'status' => false,
                        'message' => "Remitter details not found."
                    ];
                    return $this->response('notvalid', $response);  
                }  
            }else { 
                $response = [
                    'errors' => "invalid!",
                    'message' => "Not Authorised!!"
                ];
                return $this->response('notvalid', $response); 
            } 
            
        }catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function sendotp($reqData){
        $result = Remitterauth::select("*")->where("mobile",$mobile)->where("is_used","0")->first();  
        if(!empty($result)){
            $array  =   array("[otp]"=>$result['authcode'],"[pin]"=>$reqData['pin']);
            $this->common->createsms(array("number" => $reqData['mobile'],"userid" => $reqData['userid'], "message" => $array,"template"=>$reqData['template']));
        }else{
            $array  =   array("[otp]"=>$this->authcode,"[pin]"=>$reqData['pin']);
            $this->imps->insertdata("remitterauth",array("userid"=>$reqData['userid'],"mobile"=>$reqData['mobile'],"authcode"=>$this->authcode));
           $this->common->createsms(array("number" => $reqData['mobile'],"userid" => $reqData['userid'],"pin"=>$reqData['pin'], "message" => $array,"template"=>$reqData['template']));
        }
        return true;
    }
    
}