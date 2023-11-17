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
use App\Models\Dmttransfer;
use App\Models\Bank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Traits\HeaderTrait;
use App\Http\Traits\ChargesTrait; 
use App\Libraries\Psdmt; 
use App\Http\Traits\RechargeTrait;
use Illuminate\Support\Facades\Auth;
use App\Libraries\Whatsapplib;
use App\Rules\IFSCCode;
class TransactionController extends Controller
{
    use CommonTrait,HeaderTrait,ChargesTrait,RechargeTrait;
    public function __construct(){
        $this->authcode =   '222111';
        $this->status = ['0'=>'Deactive','1'=>'Active'];
        $this->unicode  	=   time().rand(11111,99999);
        $this->dmtstatus=array('REFUNDED','SUCCESS','IN PROCESS','SENT TO BANK','HOLD','FAILED'); 
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
            $pipe    = $request->pipe;
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
                                            $api_type   =  'PSDMT'; 
                                            $slot =  ChargesTrait::calculatedmtslot($amount,$userdata->id,5);
                                            if(!empty($slot) && is_array($slot)){
                                                $totalAmount    =   0;
                                                    foreach($slot   as  $key=> $value){
                                                        if($value['amount'] >= '100') { 
                                                            $reqData = array(
                                                                "uid"               =>      $userdata->id,
                                                                "did"               =>      $userdata->distributor, 
                                                                "sdid"              =>      $userdata->supdistributor,
                                                                "amount"            =>      $value['amount'],
                                                                "acharges"          =>      $value['agent_charges'],
                                                                "dcharges"          =>      $value['dt_charges'], 
                                                                "scharges"          =>      $value['sd_charges'],
                                                                "acno"              =>      $receiver['accno'],
                                                                "benename"          =>      $receiver['name'],
                                                                "mobile"            =>      $remitter['mobile'],
                                                                "bankname"          =>      $receiver['bankname'],
                                                                "bankid"            =>      $receiver['bankid'],
                                                                "ifsc"              =>      strtoupper($receiver['ifsc']),
                                                                "dmrtype"           =>      $dmrtype, 
                                                                "access_mode"       =>      "Site",
                                                                "api_type"          =>      $api_type, 
                                                                "pipe"              =>      $pipe,
                                                                "depositorid"       =>      $remitter['id'],
                                                                "remittername"      =>      $remitter['fname'],
                                                                "receiverid"        =>      $receiver['id'],
                                                                "bcagentcode"       =>      $userdata->id,
                                                                "unicode"           =>      $unique,
                                                                "reference"         =>      0,
                                                                "txntype"           =>      0,
                                                                "counter"           =>      $key,
                                                            ); 
                                                              $txn_status = RechargeTrait::dmtprocess($reqData); 
                                                              if ($txn_status['status'] == 1) {
                                                                $arr[$key]['status'] = true;
                                                                $arr['txnid'][$key] = $txn_status['orderid'];
                                                                $totalAmount += $value['amount'];
                                                            }else{
                                                                $arr[$key]['status'] = false;
                                                                $arr[$key]['message'] = $txn_status['message'];
                                                                $arr[$key]['response_code'] = $txn_status['status'];
                                                                $arr['txnid'][$key] = "0000";
                                                                break;
                                                            }
                                                            if ($txn_status['status'] == 1) {
                                                                $arr[$key]['status'] = true;
                                                                $arr['txnid'][$key] = $txn_status['orderid'];
                                                                $totalAmount += $value['amount'];
                                                            }else{
                                                                $arr[$key]['status'] = false;
                                                                $arr[$key]['message'] = $txn_status['message'];
                                                                $arr[$key]['response_code'] = $txn_status['status'];
                                                                $arr['txnid'][$key] = "0000";
                                                                break;
                                                            }
                                                        }else{
                                                            $arr[$key]['status'] = false;
                                                            $arr[$key]['message'] = 'Amount Should be greater than 99';
                                                            $arr[$key]['response_code'] = 'Amount Should be greater than 99';
                                                            $arr['txnid'][$key] = "0000";
                                                            break;
                                                        }
                                                    }   
                                                    
                                                    if($arr[0]['status'] == false) {
                                                     
                                                        $response = [
                                                            'status' => false,
                                                            'response' => 3, 
                                                            'statuscode' => 2001, 
                                                            'message' =>  $arr[0]['message'] != ''?$arr[0]['message']:"Something went wrong pleae try later or contact your distributror or customer care."
                                                        ];
                                                        return $this->response('notvalid', $response);   
                                                    }else{
                                                        $impsstatus =1; //$this->imps->getdata("config", array("id" => 3), "row_array");
                                                        if($impsstatus == 1){
                                                            $is_okay    =   true;
                                                            $txn    =  Dmttransfer::select('*')->where('refid',$unique)->where('apitype',"PSDMT")->where('status',2)->get(); 
                                                            foreach($txn    as  $key=> $txndata){ 
                                                                if($is_okay){ 
                                                                    $bankdata   =  Bank::select('*')->where('id',$txndata['bankid'])->first();   
                                                                    if($bankdata['impsstatus'] == 1 && $bankdata['counter'] == 0  || true){ 
                                                                        $txnProcess     =   Dmttransfer::select('*')->where('id',$txndata['id'])->where('apitype',"PSDMT")->where('status',2)->first(); 
                                                                        if(!empty($txnProcess)){
                                                                            $ackno  =   $txndata['id'];
                                                                             Dmttransfer::where("id",$txndata['id'])->update(array("status"=>3,"dateupdate"=>date('Y-m-d'),"ackno" => $ackno));
                                                                             $getcustomer = Depositor::select('*')->where('mobile',$txndata['mobile'])->first();   
                                                                             $getreciever = Beneficiary::select('*')->where('depositorid',$getcustomer['id'])->where('accno',$txndata['acno'])->first(); 
                                                                             $UBene = Beneaclimit::where('acno',$getreciever['accno'])->decrement('monthlimit', $txndata['amount']); 
                                                                            $verifyData     =   array(
                                                                                                    'mobile' => $txndata['mobile'],
                                                                                                    'referenceid' => self::randString(5).'PAYES'.$ackno,
                                                                                                    'pipe' =>  $pipe,
                                                                                                    'pincode' => 110015,
                                                                                                    'address' => $getcustomer['address'],
                                                                                                    'dob' => $getcustomer['dob'],
                                                                                                    'gst_state' => '07',
                                                                                                    'bene_id' => $getreciever['bene_id'],
                                                                                                    'txntype' => $txndata['transfertype'],
                                                                                                    'amount' => $txndata['amount']
                                                                                                );
                                                                                             
                                                                            $result =  Psdmt::transact($verifyData);
                                                                            
                                                                            if(isset($result) && $result['status']==1 && $result['response']==1){
                                                                                if($result['txn_status'] == 1) {
                                                                                    $status         =   1;
                                                                                    $benename       =   $result['data']['recipient_name'];//$this->user->removespecial($result['benename']);
                                                                                    $txn_remarks    =   $result['data']['remarks'];
                                                                                    Dmttransfer::where("id",$txndata['id'])->update(array("status"=>$status,"dateupdate"=>date('Y-m-d'),"ackno" =>  $result['data']['utr'],
                                                                                    "benename"=>$benename,"remarks"=>$txn_remarks)); 
                                                                                    $txnProcess     =    Dmttransfer::select('*')->where('id',$txndata['id'])->where('apitype',"PSDMT")->first(); 
                                                                                    $is_okay    =   true;
                                                                                    $response = [
                                                                                        'message' => "Transaction Processed. Kindly wait for Receipt.",
                                                                                        'txnid'=>$txnProcess['txnid'],
                                                                                        'bankname'=>$txnProcess['bankname'],
                                                                                        'acno'=>$txnProcess['acno'],
                                                                                        'benename'=>$txnProcess['benename'],
                                                                                        'mobile'=>$txnProcess['mobile'],
                                                                                        'amount'=>$txnProcess['amount'],
                                                                                        'charges'=>$txnProcess['charges'],
                                                                                        'transfertype'=>$txnProcess['transfertype'],
                                                                                        'ifsccode'=>$txnProcess['ifsccode'],
                                                                                        'utr'=>$txnProcess['utr'],
                                                                                        'status'=>$this->dmtstatus[$txnProcess['status']],
                                                                                        'dateadded'=>$txnProcess['dateadded'],
                                                                                        'remarks'=>$txnProcess['remarks'],
                                                                                        'customercharge'=>$txnProcess['customercharge'],
                                                                                        'gst'=>$txnProcess['gst'], 
                                                                                        'tds'=>$txnProcess['tds'],
                                                                                        'netcommission'=>$txnProcess['netcommission'],
                                                                                        'is_reciept'=>true,
                                                                                        'statuscode'=>200,
                                                                                        'response'=>1
                                                                                    ];
                                                                                return $this->response('success', $response);  
                                                                                }elseif($result['txn_status'] == 2 || $result['txn_status'] == 3 || $result['txn_status'] == 4){

                                                                                    $status         =   4;
                                                                                    $benename       =   $result['data']['recipient_name'];//$this->user->removespecial($result['benename']);
                                                                                    $txn_remarks    =   $result['data']['remarks']; 
                                                                                    Dmttransfer::where("id",$bankdata['id'])->update(array("impsstatus"=>0,"remarks"=>$result['remarks'])); 
                                                                                    Dmttransfer::where("id",$txndata['id'])->update(array("utr"=>$result['data']['utr'],"ackno"=>$result['data']['utr'],"remarks"=>$txn_remarks,"status"=>$status,"dateupdate"=>date('Y-m-d'))); 
                                                                                    $response = [
                                                                                        'status' => false,
                                                                                        'statuscode' => 2001,  
                                                                                        'message' =>  $result['message']
                                                                                    ];
                                                                                    return $this->response('notvalid', $response);  
                                                                                }else{
                                                                                    if($result['txn_status'] == 5){
                                                                                        Dmttransfer::where("id",$txndata['id'])->update(array("utr"=>$result['data']['utr'],"ackno"=>$result['data']['utr'],"remarks"=>$txn_remarks,"status"=>5,"dateupdate"=>date('Y-m-d')));  
                                                                                    }
                                                                                    $response = [
                                                                                        'status' => false,
                                                                                        'statuscode' => 2001,  
                                                                                        'message' =>  $result['message']
                                                                                    ];
                                                                                    return $this->response('notvalid', $response); 
                                                                                }
                                                                            }else{
                                                                                if(isset($result['txn_status'])){
                                                                                    $status         =   $result['txn_status'];
                                                                                    $txn_remarks    =   $result['message'];
                                                                                    $update_array   =   array("remarks"=>$txn_remarks,"status"=>$status,"dateupdate"=>date('Y-m-d'));
                                                                                    Dmttransfer::where("id",$txndata['id'])->update($update_array); 
                                                                                }
                                                                                $response = [
                                                                                    'status' => false,
                                                                                    'statuscode' => 2001, 
                                                                                    'ResponseCode'=>300,
                                                                                    'message' =>  $result['message']
                                                                                ];
                                                                                return $this->response('notvalid', $response);   
                                                                            }
                                                                        }else{
                                                                            $response = [
                                                                                'status' => false,
                                                                                'statuscode' => 2001, 
                                                                                'message' => "Transaction not found"
                                                                            ];
                                                                            return $this->response('notvalid', $response);    
                                                                        }   
                                                                    }else{
                                                                        $response = [
                                                                            'status' => false,
                                                                            'statuscode' => 2001, 
                                                                            'message' => "IMPS Service is not active for given bank."
                                                                        ];
                                                                        return $this->response('notvalid', $response);   
                                                                    }   
                                                                }else{
                                                                    $is_okay    =   false;
                                                                } 
                                                            }
                                                        }else{
                                                            $response = [
                                                                'status' => false,
                                                                'statuscode' => 2001, 
                                                                'message' => "Something went wrong"
                                                            ];
                                                            return $this->response('notvalid', $response);    
                                                        }
                                                    }  
                                            }else{
                                                $response = [
                                                    'status' => false,
                                                    'statuscode' => 2001, 
                                                    'message' => "Please wait for the commissions."
                                                ];
                                                return $this->response('notvalid', $response);      
                                            }
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
    public static function randString($length, $charset='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789')
    {
        $str = '';
        $count = strlen($charset);
        while ($length--) {
            $str .= $charset[mt_rand(0, $count-1)];
        }
        return $str;
    }
}