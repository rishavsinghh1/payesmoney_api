<?php
namespace App\Http\Controllers\Dmt; 
use App\Http\Controllers\Controller;
use App\Http\Traits\CommonTrait; 
use App\Models\User;
use App\Models\frm;
use App\Models\Beneaclimit;
use App\Models\Beneficiary;
use App\Models\UniqueRef;
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
class TransactionController extends Controller
{
    use CommonTrait,HeaderTrait,ChargesTrait;
    public function __construct(){
        $this->authcode =   '222111';
        $this->status = ['0'=>'Deactive','1'=>'Active'];
        $this->unicode  	=   time().rand(11111,99999);
    } 
 
    public function initiate(Request $request){
        try {
            $validated = Validator::make($request->all(), [  
                "mobile"        => 'required|digits:10|numeric',   
                "bene_id"      =>'required',
                "amount"        =>'required', 
            ]);
            if ($validated->fails()) {
                $message   = $this->validationResponse($validated->errors());
                return $this->response('validatorerrors', $message);
            }
            $userdata = Auth::user();
            $mobile     =   $request->mobile; 
            $amount = (int)$request->amount;   
            $receiver = $request->bene_id;
            if ($userdata && in_array($userdata->role, array(5))) {  
                $remitter = Depositor::select('*')->where('mobile',$mobile)->first();  
                $getreceiver = Beneficiary::select('*')->where('bene_id',$receiver)->where('depositorid',$remitter->id)->first();
                if(!empty($remitter) && !empty($receiver)){ 
                    $beneaclimit = Beneaclimit::select('*')->where('acno',$getreceiver->accno)->first();  
                    if($beneaclimit['monthlimit'] >= $amount){
                        $charges      =   0;
                        $gstcharges   =   $amount * 1/100;
                        $charges      =   $gstcharges;
                        if($charges < 10){
                            $charges = 10;
                        }
                        $unique =   $this->unicode;
                        $uniqueAdd = UniqueRef::insertGetId(array("userid"=>$userdata->id,"amount"=>$amount,"refrenceno"=>$unique)); 
                        $data['amount']         =   $amount;
                        $data['remitter']       =   $remitter;
                        $data['receiver']       =   $getreceiver; 
                        $data['charges']        =   $charges;
                        $data['unicode']        =   $unique; 
    
                        $warr = [];
                        $warr[] = ["key"=>base64_encode("main"),"balance"=>$userdata->cd_balance,"name"=>"Main"];
                        //$warr[] = ["key"=>base64_encode("aeps"),"balance"=>$this->valid_user['aepswallet'],"name"=>"Cash-In"];
                        $data['wallet_type']  = $warr;
                        $response = [
                            'status' => true,
                            'statuscode' => 200, 
                            'data' => $data, 
                            'message'=> "Data Feteched"
                        ];
                        return $this->response('success', $response);  
                    }else{
                        $response = [
                            'status' => false,
                            'statuscode' => 2001,
                            'message' => "Monthly limit of beneficiary account exceeded."
                        ];
                        return $this->response('notvalid', $response);   
                    }
                }else{
                    $response = [
                        'status' => false,
                        'statuscode' => 2001,
                        'message' => "Remitter or Beneficiary not found.Something went wrong. Please re-initiate transaction."
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

    public function dotransaction(Request $request){
        try {
            $validated = Validator::make($request->all(), [  
                "mobile"        => 'required|digits:10|numeric',   
                "bene_id"      =>'required',
                "amount"        =>'required', 
                "unicode"        =>'required', 
                "txntype"        =>'required', 
            ]);
            if ($validated->fails()) {
                $message   = $this->validationResponse($validated->errors());
                return $this->response('validatorerrors', $message);
            }
            $userdata = Auth::user();
            $mobile     =   $request->mobile; 
            $amount = (int)$request->amount;   
            $receiver = $request->bene_id;
            $dmrtype  = strtoupper($request->txntype);
            $unique   = $request->unicode;
            $getcharge =   array("value"=>$amount*0.01); 
            if ($userdata && in_array($userdata->role, array(5))) { 
                $remitter = Depositor::select('*')->where('mobile',$mobile)->where('status',1)->first();   
                $receiver = Beneficiary::select('*')->where('bene_id',$receiver)->where('depositorid',$remitter->id)->where('status',1)->first();
                if(!empty($remitter)){
                    if(!empty($receiver)){
                        $frm    = frm::select("*")->where("status",1)->where("frm_details",$mobile)->first();
                        if(!$frm){
                            $getunique =UniqueRef::select('*')->where('refrenceno',$unique)->where('status',0)->first();  
                            if (!empty($getunique)) {
                               // UniqueRef::where("refrenceno",$getunique->id)->update(array("status" => 1));  
                                $bankdata   =  Bank::select('*')->where('id',$receiver->bankid)->first();  
                                if($bankdata->accverification == 1){
                                    $allowedtype    =   array("IMPS","NEFT");
                                }else{
                                    $allowedtype    =   array("NEFT");
                                } 
                                if(in_array($dmrtype,$allowedtype)){
                                    if(($userdata->cd_balance - $userdata->minbalance)  >= ($getcharge['value'] + $amount)){
                                        if ($amount <= $remitter->limit || $amount <= $remitter->limit_2 || $amount <= $remitter->limit_3) {
                                            dd($amount);
                                        }else{
                                            $response = [
                                                'status' => false,
                                                'statuscode' => 2001, 
                                                'message' => "Monthly limit of remitter is over."
                                            ];
                                            return $this->response('notvalid', $response);     
                                        }  
                                    }else{
                                        $response = [
                                            'status' => false,
                                            'statuscode' => 2001, 
                                            'message' => "Insufficient fund in your account. Please topup user wallet before posting any transaction."
                                        ];
                                        return $this->response('notvalid', $response);    
                                    }
                                }else{
                                    $response = [
                                        'status' => false,
                                        'statuscode' => 2001,
                                        'message' => "Selected Bank do not support ".$dmrtype." type transaction."
                                    ];
                                    return $this->response('notvalid', $response);  
                                }
                            } else {
                                $response = [
                                    'status' => false,
                                    'statuscode' => 2001,
                                    'message' => "Transaction with same reference number already exists."
                                ];
                                return $this->response('notvalid', $response);   
                            }

                        }else{
                            $response = [
                                'status' => false,
                                'statuscode' => 2001,
                                'message' => "Remittance is not allowed in this account number."
                            ];
                            return $this->response('notvalid', $response);  
                        }
                    }else{
                        $response = [
                            'status' => false,
                            'statuscode' => 2001,
                            'message' => "Invalid Beneficiary Details."
                        ];
                        return $this->response('notvalid', $response);  
                    }
                }else{
                    $response = [
                        'status' => false,
                        'statuscode' => 2001,
                        'message' => "Invalid Remitter Details"
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
}