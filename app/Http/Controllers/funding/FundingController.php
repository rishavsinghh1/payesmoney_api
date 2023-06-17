<?php
namespace App\Http\Controllers\funding;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Traits\CommonTrait;
use App\Http\Traits\HeaderTrait;
use App\Http\Traits\FundTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Creditrequest;
use App\Models\CompanyBank;
use App\Models\CashTransaction;
use App\Models\UserPasswordDetails as UserPassword;
class FundingController extends Controller
{
    
    use CommonTrait,HeaderTrait,FundTrait;
    public function __construct() {
        $this->status = ['0' => "Pending", '1' => "Active", '2' => "Deactive"];
        $this->requesttypes = [0 => 'Cash Deposit', 1 => 'NEFT', 2 => 'RTGS/IMPS', 3 => 'Bank Transfer', 4 => 'other', 5 => 'funding'];
        $this->requeststatus = ['Rejected', 'Approved', 'Pending', 'Authorization Pending', 'Hold'];
    }

    public function create(Request $request){
      $userdata = Auth::user(); 
        if ($userdata && in_array($userdata->role, array(3, 4, 5))) {
            try {
                    $validated = Validator::make($request->all(), [
                        'requesttype'   => 'required', 
                        "amount"      => 'required|numeric'  
                        
                    ]);
                    if ($request->requesttype != 4) {
                        $validated->sometimes('txnid', 'required', function ($input) {
                            return ($input->txnid === null) ;
                        });
                        $validated->sometimes('depositeddate', 'required', function ($input) {
                            return ($input->depositeddate === null) ;
                        });
                        $validated->sometimes('bankid', 'required', function ($input) {
                            return ($input->bankid === null) ;
                        }); 
                    }
                    if ($request->requesttype == 0) {
                        $validated->sometimes('depositedbranch', 'required', function ($input) {
                            return ($input->depositeddate === null) ;
                        }); 
                    }
                    if ($request->requesttype != 4) {
                        $depositeddate = date("Y-m-d", strtotime($request->depositeddate));
                    }
                    else
                    {
                        $depositeddate = date('Y-m-d');
                    }
                    if ($validated->fails()) {
                        $message   = $this->validationResponse($validated->errors());
                        return $this->response('validatorerrors', $message);
                    }
                    if ($request->requesttype == 4) {
                        $requestdata["userid"] = $userdata->id;
                        $requestdata["amount"] = $request->amount;
                        $requestdata["status"] = 2;
                        $requestdata["depositeddate"] = $depositeddate;
                        $requestdata["requesttype"] = $request->requesttype;
                        $requestdata["requestremark"] =$request->remarks;
                        $requestdata["referencenumber"] = $request->txnid;
                    } else {
                        $requestdata["userid"] = $userdata->id;
                        $requestdata["amount"] = $request->amount;
                        $requestdata["referencenumber"]= $request->txnid;
                        $requestdata["depositeddate"] = $depositeddate;
                        $requestdata["status"] = 2;
                        $requestdata["requesttype"] = $request->requesttype;
                        $requestdata["bankid"] = $request->bankid;
                    }
                    if ($request->requesttype == 0) {
                        $requestdata["requestremark"] = "Deposited Branch " . $request->depositedbranch;
                    }
                    $requestdata["addeddate"] = date("Y-m-d");  
                    $requestfund = Creditrequest::insert($requestdata);  
                    if($requestfund){
                        $response = [
                            'message' => "Fund request successfully raised"
                        ];
                        return $this->response('success', $response);
                    }else{
                        $response = [
                            'errors' => "invalid!",
                            'message' => "Please try again later!"
                        ];
                        return $this->response('notvalid', $response);
                    }
                } catch (\Throwable $th) {
                    return $this->response('internalservererror', ['message' => $th->getMessage()]);
                }

        }else { 
                $response = [
                    'errors' => "invalid!",
                    'message' => "Validation error your request is not allow at server end"
                ];
                return $this->response('notvalid', $response); 
        }  
            

    } 
    public function AdminAddFund(Request $request){
        $userdata = Auth::user(); 
        if ($userdata && in_array($userdata->role, array(1))) {
        try {
                $validated = Validator::make($request->all(), [ 
                    "amount"      => 'required|numeric'  
                    
                ]);
                if ($validated->fails()) {
                    $message   = $this->validationResponse($validated->errors());
                    return $this->response('validatorerrors', $message);
                }
            $amount = $request->amount;
            if ($userdata->cd_balance >= $amount) {
                $row = $this->getadmin(20231002);
                if (!empty($row)) {
                     //superadmin
                     $sopening = $userdata->cd_balance;
                     $sclosing = $userdata->cd_balance - $amount;
                     //accounts
                     $aopening = $row->cd_balance;
                     $aclosing = $aopening + $amount;
                     $acredit = $row->credit + $amount; 
                    $request = array(
                        "sid" => 20231001,
                        "aid" => 20231002,
                        "amount" => $amount,
                        "aopening" => $aopening,
                        "aclosing" => $aclosing,
                        "sopening" => $sopening,
                        "sclosing" => $sclosing,
                        "stype" => "debit",
                        "atype" => "credit",
                        "narration" => $userdata->username . " transfer Rs." . $amount . " to " . $row->username, 
                        "refunded" => 0,
                        "ipaddress" => $request->ip(),
                        "credit" => $acredit,
                        "ttype" => 0
                    );
                    $creditdata = $this->addfundadmin($request);
                    if($creditdata){
                        $response = [
                            'message' => "Fund Successfully Transefer!"
                        ];
                        return $this->response('success', $response);
                    }else {
                        $response = [
                            'errors' => "invalid!",
                            'message' => "Unable to add fund Admin!"
                        ];
                        return $this->response('notvalid', $response);  
                    }
                }else {
                    $response = [
                        'errors' => "invalid!",
                        'message' => "Unable to find creditor details please contact tech team"
                    ];
                    return $this->response('notvalid', $response);  
                }
                   
            }else{
                $response = [
                    'errors' => "invalid!",
                    'message' => "Insufficient fund in the account"
                ];
                return $this->response('notvalid', $response); 
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
        }else { 
            $response = [
                'errors' => "invalid!",
                'message' => "Restricted attemp!!only admin allowed!!"
            ];
            return $this->response('notvalid', $response); 
        } 
    } 
    public function addfundsuperadmin(Request $request){
        $userdata = Auth::user(); 
        if ($userdata && in_array($userdata->role, array(1,2))) {
            try {
                $validated = Validator::make($request->all(), [ 
                    "amount"      => 'required|numeric' ,
                    "bankid"      => 'required|numeric'  
                    
                ]);
                if ($validated->fails()) {
                    $message   = $this->validationResponse($validated->errors());
                    return $this->response('validatorerrors', $message);
                }
            $amount = $request->amount;
            $bankid = $request->bankid;
            $debitor =  CompanyBank::find($bankid); 
          
            if($debitor->is_transfer_allowed == 0){
                $response = [
                    'errors' => "invalid!",
                    'message' => "Bank Down Or Transfer not allowed To Send!!"
                ];
                return $this->response('notvalid', $response); 
            }
            if ($debitor->balance >= $amount) {
                $row = $this->getSuperadmin(20231001); 
                if (!empty($row)) {
                       //superadmin
                       $sopening = $row->cd_balance;
                       $sclosing = $row->cd_balance + $amount;
                       //updating Creditor fund  
                       $updateCreditor =  DB::table('users')->where('id', $row->id)->update(['cd_balance' =>DB::raw('cd_balance+'.$amount),'credit' =>DB::raw('credit+'.$amount)]);    
                    $requestdata = array(
                        "sid" => 20231001, 
                        "amount" => $amount, 
                        "sopening" => $sopening,
                        "sclosing" => $sclosing,
                        "stype" => "credit", 
                        "narration" => $debitor->name . "(".$debitor->accno.")- transfer Rs." . $amount . " to " . $row->username, 
                        "refunded" => 0,
                        "ipaddress" => $request->ip(), 
                        "ttype" => 0,
                        "addeddate" => date('Y-m-d')
                    );
                    $deduct_bank_balance =  DB::table('companybank')->where('id',$debitor->id)->update(['balance' =>DB::raw('balance-'.$amount)]); 
                    $txnData = CashTransaction::insert($requestdata); 
                    if ($txnData) {
                        $response = [
                            'message' => "Fund Added Successfully!"
                        ];
                        return $this->response('success', $response);
                    }else {
                        $response = [
                            'errors' => "invalid!",
                            'message' => "Unable to add fund Admin!"
                        ];
                        return $this->response('notvalid', $response);  
                    } 
                }else {
                    $response = [
                        'errors' => "invalid!",
                        'message' => "Unable to find creditor details please contact tech team"
                    ];
                    return $this->response('notvalid', $response);  
                }
                   
            }else{
                $response = [
                    'errors' => "invalid!",
                    'message' => "Insufficient fund in the account"
                ];
                return $this->response('notvalid', $response); 
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
        }else { 
            $response = [
                'errors' => "invalid!",
                'message' => "Restricted attemp!!only admin allowed!!"
            ];
            return $this->response('notvalid', $response); 
        } 
    } 
    public function transferfund(Request $request){
       $userdata = Auth::user();   
       if ($userdata) {
        if ($userdata && in_array($userdata->role, array(1,3,4))) {
            if ($userdata->cd_balance != 0 && $userdata->cd_balance >= $request->amount) {
                try {
                    $validated = Validator::make($request->all(), [
                        'userid'   => 'required', 
                        "amount"      => 'required|numeric'  
                        
                    ]);
                    if ($validated->fails()) {
                        $message   = $this->validationResponse($validated->errors());
                        return $this->response('validatorerrors', $message);
                    } 
                    $requestdata["userid"] = $request->userid;
                    $requestdata["debitor"] = $userdata->id;
                    $requestdata["amount"] = $request->amount;
                    $requestdata["status"] = 2;
                    $requestdata["requesttype"] = 5;
                    $requestdata["addeddate"] =  date('Y-m-d');
                    $requestdata["depositeddate"] =  date('Y-m-d'); 
                    $Addcredit = Creditrequest::insert($requestdata);  
                    // $insertId =DB::getPdo()->lastInsertId();
                    // if($Addcredit){
                        $response = $this->transferLoadfund(8);
                        dd($response);
                    
                } catch (\Throwable $th) {
                    return $this->response('internalservererror', ['message' => $th->getMessage()]);
                }
            } else {
                $response = [
                    'errors' => "invalid!",
                    'message' => "You have low balance in your account please recharge or reduce the amount of transfer"
                ];
                return $this->response('notvalid', $response);  
            }
        }else { 
            $response = [
                'errors' => "invalid!",
                'message' => "Restricted area!!"
            ];
            return $this->response('notvalid', $response); 
        } 
       }else{
        $response = [
            'errors' => "invalid!",
            'message' => "Some Error Occure !! Re-login"
        ];
        return $this->response('notvalid', $response);  
       }
    }
    

}