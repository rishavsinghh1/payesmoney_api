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
use App\Models\RefundTransaction;
use Illuminate\Support\Facades\Auth;
class ManualProcessController extends Controller
{
    use CommonTrait,HeaderTrait,ChargesTrait,RechargeTrait;
    public function __construct() {     
    }
    public function updateSucesstoFailedRec(Request $request)
    { 
        $validator = Validator::make($request->all(), [
            'txnid'  =>  'string|required',
            'status'     =>  'numeric|required',
            'remark'   =>  'string|required',
        ]);
        if ($validator->fails()) {
            $message   = $this->validationResponse($validator->errors());
            return $this->response('validatorerrors', $message);
        }
        $info   = Recharge::select('*')   
                ->where('refid',$request['txnid']) 
                ->first(); 
        $userdetails = User::select("*")->where(array('id'=>$info['userid']))->first(); 
       // dd($userdetails->supdistributor);die;
        if($info){
            if($info->refunded == 0){
                if($request['status'] === "3"){ 
                        $post1['ttype'] = 0;
                        $post1['utype'] = 'credit';
                        $post1['comm']  =  0;
                        $post1['dcomm']  = 0;
                        $post1['sdcomm'] = 0;
                        $post1['profit'] = 0;
                        $post1['ttype'] = 6;
                        $post1['uid']    = $info->userid;
                        $post1['amount'] = $info['amount'] - $info['comm'];
                        $post1['narration'] = "Manual Transaction REFUND for A/C ".$info['canumber']." amount of ".$info['amount'] ." Txn Id "."PMYR0023".$info->txnid; 
                        $post1['creditamount'] = $info['amount'] - $info['comm']; 
                        $rechcredit = RechargeTrait::credit($post1); 
                        $txnupdate = [ 
                            'refundtxnid' => $rechcredit['txnno'],
                            'refunded' => 1,
                            'status'=> 4, 
                            'operatorid' =>'Manual Refund ('.date('d-m-Y h:i:s a').')',
                            'daterefunded' =>date('Y-m-d')
                        ];
                    $isupdate = Recharge::where('txnid', $info->txnid)->update($txnupdate);  
                   // $isupdatecash = CashTransaction::where('id',$info->txnid)->update(['refunded' => 1]);

                    $requestdata["txn_id"] = $info->txnid;
                    $requestdata["refundtxnid"] = $rechcredit['txnno'];
                    $requestdata["uid"] = $info->userid;
                    $requestdata["amount"] =$info['amount'];
                    $requestdata["canumber"] = $info['canumber'];
                    $requestdata["uid"] = $info->userid;
                    $requestdata["sdid"] = $userdetails->supdistributor;
                    $requestdata["did"] = $userdetails->distributor;
                    $requestdata["dcomm"] = $info->dcomm;
                    $requestdata["sdcomm"] = $info->sdcomm; 
                    $requestdata["status"] = 2;
                    $requestdata["ttype"] = 6;  
                    $requestdata["addeddate"] = date('Y-m-d');
                    $requestdata["order_id"] =$info->refid;  
                    $isRefundT= RefundTransaction::insert($requestdata);  
                    if($isRefundT){
                        $data = [
                            'statuscode'    => 200,
                            'status'        => true,
                            'responsecode'  => 1,
                            'message' => "Refund Success"
                        ];  
                    }else{
                        $data = [
                            'statuscode'    => 200,
                            'status'        => false,
                            'responsecode'  => 0,
                            'message' => "Some Error Occured!!"
                        ]; 
                    }
                    
                }else{
                    $data = [
                        'statuscode'    => 200,
                        'status'        => true,
                        'responsecode'  => 1,
                        'message' => "Status not found!!"
                    ];
                }
            }else{
               $data = [
                'statuscode'    => 200,
                'status'        => true,
                'responsecode'  => 1,
                'message' => "Status Already Updated"
                ];   
            }
        
        }else{  
            $data = [
                'statuscode'    => 200,
                'status'        => true,
                'responsecode'  => 1,
                'message' => "Recharge Data not Found!!"
            ]; 
        }

       
        return response()->json($data,200);

    }
}