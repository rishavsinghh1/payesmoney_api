<?php
namespace App\Http\Controllers\Recharge; 
use App\Http\Controllers\Controller;
use App\Http\Traits\CommonTrait;
use App\Models\Rechargeoperator;
use App\Models\Recharge;
use App\Models\UniqueRef;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Traits\HeaderTrait;
use App\Http\Traits\ChargesTrait;
use App\Http\Traits\RechargeTrait;
use App\Libraries\Rechargelib; 
use App\Models\CashTransaction;
use Illuminate\Support\Facades\Auth;
class Dthrecharge extends Controller
{
    use CommonTrait,HeaderTrait,ChargesTrait,RechargeTrait;
    public function __construct()
    {
        $this->status = ['0'=>'Deactive','1'=>'Active'];
    }
    
    public function dorecharge(Request $request)
    {
        try {
            $validated = Validator::make($request->all(), [ 
                "operator"     => 'required|max:4|min:2',
                "canumber"      => 'required|numeric',
                "amount"        => 'required||numeric|gt:99',
                "referenceid"   => 'required', 
                 
            ]);
             if ($validated->fails()) {
                    $message   = $this->validationResponse($validated->errors());
                    return $this->response('validatorerrors', $message);
                }
            $userdata = Auth::user();
            if ($userdata && in_array($userdata->role, array(1,5))) {
                $amount     =   $request->amount;
                $unique     =   $request->referenceid; 
                $operator   =   Rechargeoperator::select("*")->where("status",1)->where("op_id",$request->operator)->first(); 
                $getunique = $unique; 
                if(!empty($getunique)){
                   if(!empty($operator)){ 
                       $charges = ChargesTrait::getrechargecomm($amount,$userdata->id,$request->operator,4); 
                        $ins_array = array(
                            "uid"           =>  $userdata->id,
                            "sdid"          =>  $userdata->supdistributor,
                            "did"           =>  $userdata->distributor,  
                            "operatorname"  =>  $operator->name,
                            "canumber"      =>  $request->canumber,
                            "amount"        =>  $amount,
                            "apitype"       =>  'BESTREC',
                            "ipaddress"     =>  $_SERVER['REMOTE_ADDR'],
                            "status"        =>  2,
                            "refid"         =>  $request->referenceid,
                            'comm'          =>  $charges['comm'],
                            'dcomm'         =>  $charges['dcomm'],
                            'sdcomm'        =>  $charges['sdcomm'], 
                         ); 
                        if($userdata->cd_balance>=$amount) {
                            $requestdata =  RechargeTrait::process($ins_array);  
                            //dd($requestdata);
                        if($requestdata['status']==1 && $requestdata['txnno']!=""){ 
                            $update_request = Recharge::where("id", $requestdata['orderid'])
                            ->update(["status" => 1]);  
                            if($update_request==1){
                                $reqData = array(
                                    'operator'      =>  $name = str_replace(' ', '_',$operator->op_id),
                                    'canumber'      =>  $ins_array['canumber'],
                                    'amount'        =>  $ins_array['amount'],
                                    'referenceid'   =>  $request->referenceid,
                                    'apiname'       => 'DTH Recharge',
                                    'method'        => 'POST'
                                );
                               
                               $rs = Rechargelib::doDthrecharge($reqData);
                               
                               if($rs['statuscode']==0){
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
                               }else if($rs['statuscode']==1){
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
                               }else if($rs['statuscode']==2){
                                $post1['ttype'] = 0;
                                $post1['utype'] = 'credit';
                                $post1['comm']  = 0;
                                $post1['dcomm']  = 0;
                                $post1['sdcomm'] =0;
                                $post1['profit'] = 0;
                                $post1['uid'] = $userdata->id;
                                $post1['amount'] = $ins_array['amount'] - $charges['comm'];
                                $post1['narration'] = "Transaction FAILED for A/C ".$ins_array['canumber']." amount of ".$ins_array['amount']; 
                                $post1['creditamount'] =$ins_array['amount'] - $charges['comm'];
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
                                    'operatorid'=>'',
                                    'operatorname'=>$operator->name,
                                    'mobile'=>$request->mobile
                                ];
                                return $this->response('notvalid', $response);
                               }else{
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
                               }
                            }else{
                                $response = [
                                    'errors' => "invalid!",
                                    'message' => $operator->name ." is Down. Please Try Again Later"
                                ];
                                return $this->response('notvalid', $response); 
                            }
                        }else { 
                            $response = [
                                'errors' => "invalid!",
                                'message' =>  $request['message']
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

    
    
    
}