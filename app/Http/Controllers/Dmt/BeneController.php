<?php
namespace App\Http\Controllers\Dmt; 
use App\Http\Controllers\Controller;
use App\Http\Traits\CommonTrait; 
use App\Models\User;
use App\Models\Beneaclimit;
use App\Models\Beneficiary;
use App\Models\Depositor;
use App\Models\Bank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Traits\HeaderTrait;
use App\Http\Traits\ChargesTrait; 
use App\Libraries\Psdmt; 
use Illuminate\Support\Facades\Auth;
use App\Libraries\Whatsapplib;
use App\Rules\IFSCCode;
class BeneController extends Controller
{
    use CommonTrait,HeaderTrait,ChargesTrait;
    public function __construct(){
        $this->authcode =   '222111';
        $this->status = ['0'=>'Deactive','1'=>'Active'];
    } 

    public function benelist(Request $request){
        try {
            $validated = Validator::make($request->all(), [  
                "mobile"      => 'required|digits:10|numeric',   
            ]);
            if ($validated->fails()) {
                $message   = $this->validationResponse($validated->errors());
                return $this->response('validatorerrors', $message);
            }
            $userdata = Auth::user(); 
            $mobile     =   $request->mobile; 
            if ($userdata && in_array($userdata->role, array(5))) {  
                $remdetails = Depositor::select('*')->where('mobile',$mobile)->first();
                if($remdetails){  
                    $benedata = Beneficiary::select('*')->where('depositorid',$remdetails['id'])->where('benetype',0)->where('status',1)->where('is_deleted',0)->get();
                    
                    if(count($benedata) === 0){
                       
                        $getDetailBene  =  Psdmt::fetchbeneficiary(array("mobile" => $remdetails['mobile'])); 
                        foreach($getDetailBene['benedata']  as  $bene){
                            $bene_id    =   $bene['bene_id'];
                            $bankid     =   $bene['bankid'];
                            $bankname   =   $bene['bankname'];
                            $name       =   $bene['name'];
                            $accno      =   $bene['accno'];
                            $ifsc       =   $bene['ifsc'];
                            $verified   =   $bene['verified']; 
                            if($bene_id != ""){
                                $distinctBene   =   Beneaclimit::where('acno',$accno)->count();  
                                if($distinctBene == 0){
                                    $BeneaclimitAdd = Beneaclimit::insertGetId(array("acno"=>$accno));  
                                }
                                $requestdata = array(
                                    "userid" => $userdata->id,
                                    "accessmode" => "Site",
                                    "depositorid" => $remdetails['id'],
                                    "bankname" => $bankname,
                                    "name" => strtoupper($name),
                                    "bankid" => $bankid,
                                    "ifsc" => $ifsc,
                                    "accno" => $accno,
                                    "status" => 1,
                                    "accounttype" => "saving",
                                    "verified" => $verified, 
                                    "is_deleted"=>0,
                                    "bene_id"=>$bene_id
                                ); 
                                $check = Beneficiary::select('*')->where('bankname',$bankname)->where('ifsc',$ifsc)->where('accno',$accno)->first(); 
                                if(empty($check)){
                                    $BeneaclimitAdd = Beneficiary::insertGetId($requestdata); 
                                }
                            }    
                        }  
                    }
                        
                        $benelist = Beneficiary::select('*')->where('depositorid',$remdetails['id'])->where('is_deleted',0)->get();
                        $i=0;
                        $d=[];
                        foreach($benelist as $value){
                            $referenceid                =  self::generateRandomString();
                            $d[$i]['id']                =   $value->id;
                            $d[$i]['depositorid']       =   $remdetails->id;
                            $d[$i]['name']              =   $value->name;
                            $d[$i]['ifsc']              =   $value->ifsc;
                            $d[$i]['accno']             =   $value->accno;
                            $d[$i]['bankname']          =   $value->bankname;
                            $d[$i]['bankid']            =   $value->bankid;
                            $d[$i]['status']            =   $value->status;
                            $d[$i]['verified']          =   $value->verified;
                            $d[$i]['bene_id']           =   $value->bene_id;
                            $d[$i]['refid']       =   $referenceid;
                            $i++;
                        }
                        $response = [
                            'status' => true,
                            'statuscode' => 200, 
                            'data' => $d, 
                            'message'=> "Beneficiary and bank details fetched successfully"
                        ];
                        return $this->response('success', $response);  
                      
                }else{
                    $response = [
                        'status' => false,
                        'statuscode' => 2001,
                        'message' => "Remitter not found "
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

    public function banklist(Request $request){
        try { 
            $userdata = Auth::user();  
            if ($userdata && in_array($userdata->role, array(5))) {  
                $bank_list = Bank::select('*')->where("status",1)->get();
              
                $imps_list = $neft_list = array();
                $i=$j=0;
                foreach($bank_list as $eachbanklist){
                    $eachbanklist['text'] = $eachbanklist['bankname'];
                    if($eachbanklist['accverification']==1){
                        $imps_list[$i++]  =  $eachbanklist; 
                    }else{
                        $neft_list[$j++]  =  $eachbanklist;
                    }
                }
                  $bank_array   =   array("imps_bank"=>$neft_list,"gramin"=>$imps_list);
                if($bank_array){ 
                $response = [
                    'status' => true,
                    'statuscode' => 200, 
                    'data' => $bank_array,
                    'message'=> "Data Feteched!!"
                ];
                return $this->response('success', $response);   
            }else{
                $response = [
                    'errors' => "invalid!",
                    'statuscode'=>2001,
                    'message' => "Something went wrong!!!"
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
    public function addreceiver(Request $request){
        try {
            $validated = Validator::make($request->all(), [  
                "mobile"        => 'required|digits:10|numeric',   
                "benename"      =>'required',
                "bankid"        =>'required',
                "accountno"     =>'required',
                "confirmaccno"  =>'required|same:accountno',
                "ifsccode"      =>['required', 'max:11']
            ]);
            if ($validated->fails()) {
                $message   = $this->validationResponse($validated->errors());
                return $this->response('validatorerrors', $message);
            }
            $userdata = Auth::user(); 
            $mobile     =   $request->mobile; 
            if ($userdata && in_array($userdata->role, array(5))) {  
                $mobile = $request->mobile; 
                $accno      =  $request->accountno;
                $ifsccode   =  strtoupper($request->ifsccode);
                $bankid     =  $request->bankid;
                $benename   =  strtoupper($request->benename);
                $verified   =   0; 
                $bankdata = Bank::select('*')->where('id',$bankid)->first(); 
                $banktype   =   $bankdata['accverification']==1?0:1;
                $remitter = Depositor::select('*')->where('mobile',$mobile)->where('status',1)->first();  
                $pincode = ($remitter['pincode'] == '')?'110015':$remitter['pincode'];
                $address = ($remitter['address'] == '')?'Delhi':$remitter['address'];
                $dob=  ($remitter['dob'] == '')?'1998-05-20':$remitter['dob'];
                $gst_state= ($remitter['gst_state'] == '')?'Delhi':$remitter['gst_state'];
                if(!empty($bankdata)){
                    $ifsccode   =   $ifsccode != ''?$ifsccode:$bankdata['ifsc'];
                    if($bankid==181){
                        $last_acc    =   substr($accno,0,4);
                        $ifsc        =   "BKID000".$last_acc;
                    }
                    $distinctBene   =   Beneaclimit::where('acno',$accno)->count();
                    if($distinctBene == 0){
                        $BeneaclimitAdd = Beneaclimit::insertGetId(array("acno"=>$accno));  
                    } 
                    $remitter   = Depositor::select('*')->where('mobile',$mobile)->first();  
                    $requestdata = array(
                        "userid" => $userdata->id,
                        "accessmode" => "API",
                        "depositorid" => $remitter['id'],
                        "bankname" => $bankdata['bankname'],
                        "name" => strtoupper($benename),
                        "bankid" => $bankid,
                        "ifsc" => $ifsccode,
                        "accno" => $accno,
                        "status" => 1,
                        "accounttype" => "saving",
                        "verified" => $verified,
                        "banktype" => $banktype,
                        "is_deleted"=>0,
                    );  
                   
                    $otp_verified       =   true;                    
                        $otp_verified_msg   =   "";                                                    
                        if($otp_verified){
                            $getreceiver = Beneficiary::select("id","bankname","name","accno","ifsc")->where('depositorid',$remitter['id'])->where('accno',$accno)->where('ifsc',$ifsccode)->first();
                            if(empty($getreceiver)){
                                $reqData = array(
                                    'mobile' => $remitter['mobile'],
                                    'benename' => $benename,
                                    'bankid' => $bankid,
                                    'accno' => $accno,
                                    'ifsccode' => $ifsccode,
                                    'verified' => $verified,
                                    'gst_state' => '07',
                                    'dob' =>$dob,
                                    'address' => $remitter['address'],
                                    'pincode' => $pincode
                                ); 
                              
                                $getresult  =  Psdmt::registerbeneficiary($reqData);   
                                if(isset($getresult) && $getresult['response'] == 1 ){
                                    $requestdata['bene_id']     =   $getresult['data']['bene_id'];
                                    $requestdata['verified']    =   $getresult['data']["verified"];  
                                    $BeneaclimitAdd = Beneficiary::insertGetId($requestdata); 
                                    if($result){ 
                                        $response = [
                                            'response' => 1,
                                            'statuscode' => 200,
                                            'message' => "Beneficiary added Successfully"
                                        ];
                                        return $this->response('success', $response);  
                                    } else {
                                        $response = [
                                            'response' => 0,
                                            'statuscode' => 2001,
                                            'message' => "Error while update. Contact customer care."
                                        ];
                                        return $this->response('notvalid', $response);  
                                    }  
                                }else{
                                    $response = [
                                        'response' => 0,
                                        'statuscode' => 2001,
                                        'message' => $getresult['message']
                                    ];
                                    return $this->response('notvalid', $response);  
                                }     
                            }else{
                                $reqData = array(
                                    'mobile' => $remitter['mobile'],
                                    'benename' => $benename,
                                    'bankid' => $bankid,
                                    'accno' => $accno,
                                    'ifsccode' => $ifsccode,
                                    'verified' => $verified,
                                    'gst_state' => '07',
                                    'dob' =>$dob,
                                    'address' => $remitter['address'],
                                    'pincode' => $pincode
                                );
                                $getresult  =  Psdmt::registerbeneficiary($reqData);  
                                if(isset($getresult) && $getresult['response'] == 2 ){
                                    $isupdate = Beneficiary::where("id",$getreceiver['id'])->update(array("bene_id"=>$getresult['data']['bene_id'],"verified"=>$getresult['data']["verified"],"status" => 1
                                    ,"is_deleted" =>0)); 
                                    $response = [
                                        'response' => 2,
                                        'statuscode' => 2001, 
                                        'message' => "Beneficiary added Successfully"
                                    ];
                                    return $this->response('success', $response);    
                                }else{
                                    $response = [
                                        'response' => 2,
                                        'statuscode' => 2001,
                                        'message' => "Unable to add beneficiary. Please Try agin later"
                                    ];
                                    return $this->response('notvalid', $response);   
                                }
                            }
                        }else{
                            $response = [
                                'response' => 0,
                                'statuscode' => 2001,
                                'message' => $otp_verified_msg
                            ];
                            return $this->response('notvalid', $response);  
                        } 
                } else {
                    $response = [
                        'response' => 0,
                        'statuscode' => 2001,
                        'message' => "Plaese select Bank."
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

    

    function generateRandomString($length = 12) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}