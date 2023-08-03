<?php
namespace App\Http\Controllers\Recharge; 
use App\Http\Controllers\Controller;
use App\Http\Traits\CommonTrait;
use App\Models\Rechargeoperator;
use App\Models\Recharge;
use App\Models\UniqueRef;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Traits\HeaderTrait;
use App\Http\Traits\ChargesTrait;
use App\Http\Traits\RechargeTrait;
use App\Libraries\Rechargelib;
use App\Models\CashTransaction;
use Illuminate\Support\Facades\Auth;
class Prepaidrecharge extends Controller
{
    use CommonTrait,HeaderTrait,ChargesTrait,RechargeTrait;
    public function __construct(){
        $this->status = ['0'=>'Deactive','1'=>'Active'];
    } 
    public function dorecharge(Request $request)
    {
        try {
            $validated = Validator::make($request->all(), [ 
                "operator"     => 'required|max:4|min:2',
                "mobile"      => 'required|digits:10|numeric',
                "amount"       => 'required|numeric|gt:0',
                "referenceid"    => 'required',   
            ]);
            $userdata = Auth::user();
        //    dd($userdata);
            if ($userdata && in_array($userdata->role, array(5))) {
                if ($validated->fails()) {
                    $message   = $this->validationResponse($validated->errors());
                    return $this->response('validatorerrors', $message);
                }
                $amount     =   $request->amount;
                $unique     =   $request->referenceid; 
                $operator   =   Rechargeoperator::select("*")->where("status",1)->where("op_id",$request->operator)->first(); 
                $getunique = $unique;
                if(!empty($getunique)){
                    if(!empty($operator)){
                       
                        $ins_array  =   array(
                            "uid"           =>  $userdata->id,
                            "sdid"          =>  $userdata->supdistributor,
                            "did"           =>  $userdata->distributor,  
                            "operatorname"  =>  $operator->name,
                            "canumber"      =>  $request->mobile,
                            "amount"        =>  $amount,
                            "apitype"       =>  'BESTAPI',
                            "ipaddress"     =>  $_SERVER['REMOTE_ADDR'],
                            "status"        =>  2,
                            "refid"         =>  $request->referenceid,
                        );

                     $charges = ChargesTrait::getrechargecomm($amount,$userdata->id,$request->operator,4);
                    
                     $ins_array['comm']      = $charges['comm'];
                     $ins_array['dcomm']     = $charges['dcomm'];
                     $ins_array['sdcomm']    = $charges['sdcomm']; 
                     if($userdata->cd_balance>=$amount) {
                        $requestdata =  RechargeTrait::process($ins_array);
                       
                        if($requestdata['status']==1 && $requestdata['txnno']!=""){ 
                            $update_request = Recharge::where("id", $requestdata['orderid'])
                            ->update(["status" => 1]);  
                            if($update_request==1){
                                $reqData = array(
                                    'operator'      =>  $operator->op_id,
                                    'canumber'      =>  $ins_array['canumber'],
                                    'amount'        =>  $ins_array['amount'],
                                    'referenceid'   =>  $request->referenceid,
                                    'apiname'       => 'Recharge',
                                    'method'        => 'POST'
                                );
                               
                               $rs = Rechargelib::dorecharge($reqData);

                               switch ($rs['statuscode']) {
                                case 0:
                                    $update_request = Recharge::where("id", $requestdata['orderid'])
                                      ->update([
                                        "status" => 1,
                                        "operatorid"=>$rs['data']['refTransactionNumber'],
                                        'ackno'=>$rs['data']['refTransactionNumber']
                                        ]); 
                                    $response = [
                                        'message' => "SUCCESS",
                                        'txnno'=>$requestdata['txnno'],
                                        'operatorid'=>$rs['data']['refTransactionNumber'],
                                        'operatorname'=>$operator->name,
                                        'mobile'=>$request->mobile
                                    ];
                                    return $this->response('success', $response);
                                    break;
                                case 1:
                                    $update_request = Recharge::where("id", $requestdata['orderid'])
                                    ->update([
                                        "status" => 1,
                                        "operatorid"=>'PM'.rand(000000,11111),
                                        'ackno'=>'PM'.rand(000000,11111),
                                    ]); 
                                    $response = [
                                        'message' => "SUCCESS",
                                        'txnno'=>$requestdata['txnno'],
                                        'operatorid'=>'PM'.rand(000000,11111),
                                        'operatorname'=>$operator->name,
                                        'mobile'=>$request->mobile
                                    ];
                                return $this->response('success', $response);
                                    break;
                                case 2:
                                    $post1['ttype'] = 0;
                                    $post1['utype'] = 'credit';
                                    $post1['comm']  = 0;
                                    $post1['dcomm']  = 0;
                                    $post1['sdcomm'] =0;
                                    $post1['profit'] = 0;
                                    $post1['uid'] = $userdata->id;
                                    $post1['amount'] = $ins_array['amount'] - $charges['comm'];
                                    $post1['narration'] = "Transaction FAILED for A/C ".$ins_array['canumber']." amount of ".$ins_array['amount']; 
                                    $post1['creditamount'] = $ins_array['amount'] - $charges['comm'];
                                    $rechcredit = RechargeTrait::credit($post1);
                                    $txnupdate = [
                                        'refundtxnid' => $rechcredit['txnno'],
                                        'refunded' => 1,
                                        'status' => 3, 
                                        'daterefunded' => date('Y-m-d'),
                                    ];
                                    $isupdate = Recharge::where('id', $requestdata['orderid'])->update($txnupdate); 
                                    $isupdatecash = CashTransaction::where('id', $requestdata['txnno'])->update(['refunded' => 1]);
                                    $response = [
                                        'message' => "FAILED",
                                        'txnno'=>$requestdata['txnno'],
                                        'operatorid'=>$rs['data']['refTransactionNumber'],
                                        'operatorname'=>$operator->name,
                                        'mobile'=>$request->mobile
                                    ];
                                    return $this->response('notvalid', $response);
                                    break;
                                default:
                                    $update_request = Recharge::where("id", $requestdata['orderid'])
                                    ->update([
                                        "status" => 1,
                                        "operatorid"=>'PM'.rand(000000,11111),
                                        'ackno'=>'PM'.rand(000000,11111),
                                    ]); 
                                    $response = [
                                        'message' => "SUCCESS",
                                        'txnno'=>$requestdata['txnno'],
                                        'operatorid'=>'PM'.rand(000000,11111),
                                        'operatorname'=>$operator->name,
                                        'mobile'=>$request->mobile
                                    ];
                                return $this->response('success', $response);
                                break;
                            }
                              
                              
                                $response = [
                                    'errors' => "invalid!",
                                    'message' => $operator->name ." is Down. Please Try Again Later"
                                ];
                                return $this->response('notvalid', $response); 
                            }
                        }else { 
                            $response = [
                                'errors' => "invalid!",
                                'message' =>  $requestdata['message']
                            ];
                            return $this->response('notvalid', $response); 
                        } 
                     }else{
                        $response = [
                            'errors' => "invalid!",
                            'message' => "Insufficient fund in your account. Please topup your wallet before initiating transaction"
                        ];
                        return $this->response('notvalid', $response); 
                     } 
                    }else { 
                        $response = [
                            'errors' => "invalid!",
                            'message' => "Operator cannot be blank"
                        ];
                        return $this->response('notvalid', $response); 
                    } 
                }else { 
                    $response = [
                        'errors' => "invalid!",
                        'message' => "Transaction with the same no. already"
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
    public function getoperator(Request $request){
        try {
            $userdata = Auth::user();
            if ($userdata && in_array($userdata->role, array(1,5))) {
                $reqData = array( 
                    'canumber'      =>  $request->number, 
                    'apiname'       => 'check operator',
                    'method'        => 'POST'
                );
               
               $data = Rechargelib::docheckOp($reqData);
               
               if($data){
                    $response = [
                        'message' => "SUCCESS",
                        'data'=>$data 
                    ];
                    return $this->response('success', $response);
                   }else{
                    $response = [
                        'message' => "FAILED", 
                        'data'=>$rs 
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
    public function getRoffer(Request $request){
        try {
            $userdata = Auth::user();
            if ($userdata && in_array($userdata->role, array(1,5))) {
                $reqData = array( 
                    'canumber'      =>  $request->number, 
                    'operator'      =>  $request->operator, 
                    'apiname'       => 'check operator',
                    'method'        => 'POST'
                );
               
               $data = Rechargelib::docheckRoffer($reqData);
               
               if($data){
                    $response = [
                        'message' => "SUCCESS",
                        'responsedata'=>$data
                    ];
                    return $this->response('success', $response);
                   }else{
                    $response = [
                        'message' => "FAILED", 
                        'responsedata'=>$data 
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
    public function getDthDetails(Request $request){
        try {
            $userdata = Auth::user();
            if ($userdata && in_array($userdata->role, array(1,5))) {
                $reqData = array( 
                    'canumber'      =>  $request->number, 
                    'operator'      =>  $request->operator, 
                    'apiname'       => 'check Dth Details',
                    'method'        => 'POST'
                );
               
               $data = Rechargelib::docDthOffer($reqData);
               
               if($data){
                    $response = [
                        'message' => "SUCCESS",
                        'responsedata'=>$data
                    ];
                    return $this->response('success', $response);
                   }else{
                    $response = [
                        'message' => "FAILED", 
                        'responsedata'=>$data 
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
    public function callbackrecharge(Request $request){
        $payload = $_REQUEST; 
        $num    =   time();
        if(isset($payload['api_ref_id'])){ $orderid = $payload['api_ref_id']; } ; 
        $info   = Recharge::select('*')   
        ->where('refid',$orderid) 
        ->first(); 
        if($info){ 
            if($info->refunded == 0){
                if ($payload['status'] == 1) {
                    $txnupdate = [
                        'operatorid' => $payload['operator_id'],
                        'ackno' => $payload['txn_id'],
                        'status' => 1, 
                    ];
                    $isupdate = Recharge::where('txnid', $info->txnid)->update($txnupdate);
                    $response = [
                        'message' => "YES"
                    ];
                    return $this->response('success', $response);
                }else if ($payload['status'] == 3) {
                    $post1['ttype'] = 0;
                    $post1['utype'] = 'credit';
                    $post1['comm']  = 0;
                    $post1['dcomm']  = 0;
                    $post1['sdcomm'] =0;
                    $post1['profit'] = 0;
                    $post1['uid'] = $info->userid;
                    $post1['amount'] = $info->amount - $info->comm;
                    $post1['narration'] = "Transaction FAILED for A/C ".$info->canumber." amount of ".$info->amount; 
                    $post1['creditamount'] =$info->amount - $info->comm;
                    $rechcredit = RechargeTrait::credit($post1);
                    $txnupdate = [
                        'refundtxnid' => $rechcredit['txnno'],
                        'refunded' => 1,
                        'status' => 3, 
                        'daterefunded' => date('Y-m-d'),
                    ];
                    $isupdate = Recharge::where('id', $info->id)->update($txnupdate); 
                    $response = [
                        'message' => "YES"
                    ];
                    return $this->response('success', $response);
                }else{
                    $response = [
                        'message' => "NO"
                    ];
                    return $this->response('success', $response);
                }
            }else{
                $response = [
                    'message' => "ALREADY"
                ];
                return $this->response('success', $response);
            }
        }else{
            $response = [
                'message' => "NOT FOUND"
            ];
            return $this->response('success', $response);
        }
    }
}