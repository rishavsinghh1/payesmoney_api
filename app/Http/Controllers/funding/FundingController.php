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
use Carbon\Carbon;
use App\Models\UserPasswordDetails as UserPassword; 
use App\Libraries\Fund;   
use DateTime;
use DateTimeZone;
class FundingController extends Controller
{
    
    use CommonTrait,HeaderTrait,FundTrait;
    public function __construct() {
        $this->status = ['0' => "Pending", '1' => "Active", '2' => "Deactive"];
        $this->requesttypes = [0 => 'Cash Deposit', 1 => 'NEFT', 2 => 'RTGS/IMPS', 3 => 'Bank Transfer', 4 => 'other', 5 => 'funding'];
        $this->requeststatus = ['Rejected', 'Approved', 'Pending', 'Authorization Pending', 'Hold'];
        $this->today      = Carbon::now()->toDateString();
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
    function transferLoadfund($reqid){ 
        $request = Creditrequest::select("*")->where("id", $reqid)->where("status", 2)->where("requesttype", 5)->first();
        if (!empty($request)) {
            $debitor = User::select("*")->where(array('id'=>$request['debitor'],"status"=>1))->whereIn('role',array(1,3,4))->first();
            $creditor = User::select("*")->where(array('id'=>$request['userid'],"status"=>1))->whereIn('role',array(2,3,4,5))->first(); 
            if(!empty($debitor) && !empty($creditor) && $debitor->id!=$creditor->id){
                $wherestmtarrayd     =   array();
                $prvclosing         =   "";
                if($debitor['role'] == 1){
                    $wherestmtarrayd["sid"]  =$debitor->id;
                    $prvclosing     =   "sclosing";
                }elseif($debitor['role'] == 3){
                    $wherestmtarrayd["sdid"]  =   $debitor->id;
                    $prvclosing     =   "sdclosing";
                }elseif($debitor['role'] == 4){
                    $wherestmtarrayd["did"]  =   $debitor->id;
                    $prvclosing     =   "dclosing";
                }else{
                    $prvclosing     =   "";
                }
                if($prvclosing !=""){
                  $cashclosing =  self::getclosing($wherestmtarrayd,$debitor->cd_balance,$prvclosing,$debitor->role);
                  $debt_closing   =   $debitor->cd_balance - $request['amount'];
                  $wherestmtarrayc = [];
                  if($creditor['role'] == 3){
                      $wherestmtarrayc["sdid"]  =   $creditor->id;
                      $prvcclosing     =   "sdclosing";
                  }elseif($creditor['role'] == 4){
                      $wherestmtarrayc["did"]  =   $creditor->id;
                      $prvcclosing     =   "dclosing";
                  }elseif($creditor['role'] == 5){
                      $wherestmtarrayc["uid"]  =   $creditor['id'];
                      $prvcclosing     = "cd_closing";
                  }elseif($creditor['role'] == 2){
                    $wherestmtarrayc["aid"]  =   $creditor->id;
                    $prvcclosing     =   "aclosing";
                   }else{
                      $prvcclosing     =   "";
                  }
                if($prvcclosing!=""){ 
                    $cashclosingCrdditer =  self::getclosing($wherestmtarrayc,$creditor->cd_balance,$prvcclosing,$creditor->role);
                    if($cashclosingCrdditer){
                        $cedit_closing   =   $creditor->cd_balance + $request['amount'];
                        $deductbalance =  DB::table('users')->where('id', $debitor->id)->update(['cd_balance' =>DB::raw('cd_balance-'.$request['amount'])]); 
                        if($deductbalance){
                            $narration = $debitor->name ."(".$debitor->username."-".$debitor->firmname.") transfer Rs.".$request['amount']." to ".$creditor->name."(".$creditor->username."-".$creditor->firmname.")";
                            $request_ins = array(  
                                "amount" => $request['amount'],
                                "narration" => $narration,
                                "status" => 1,
                                "refunded" => 0,
                                "ipaddress" => $_SERVER['REMOTE_ADDR'],
                                "ttype" => 0,
                                "addeddate" => date('Y-m-d'),
                            );
                            if ($debitor->role == 1) {
                                $request_ins["sid"] = $debitor['id'];
                                $request_ins["sopening"] = $debitor['cd_balance'];
                $request_ins["sclosing"] = $debt_closing;
                $request_ins["stype"] = "debit"; 
            } elseif ($debitor->role == 3) {
                $request_ins["sdid"] = $debitor['id'];
                $request_ins["sdopening"] = $debitor['cd_balance'];
                $request_ins["sdclosing"] = $debt_closing;
                $request_ins["sdtype"] = "debit"; 
            } elseif ($debitor->role == 4) {
                $request_ins["did"] = $debitor['id'];
                $request_ins["dopening"] = $debitor['cd_balance'];
                $request_ins["dclosing"] = $debt_closing;
                $request_ins["dtype"] = "debit";
            } else {
                $return['status']   =   0;
                $return['message']  =   "This transaction cannot be processed. Please try later.";
            }
            if ($creditor->role == 3) {
                $request_ins["sdid"] = $creditor['id'];
                $request_ins["sdopening"] = $creditor['cd_balance'];
                $request_ins["sdclosing"] = $cedit_closing;
                $request_ins["sdtype"] = "credit"; 
            } elseif ($creditor->role == 4) {
                $request_ins["did"] = $creditor['id'];
                $request_ins["dopening"] = $creditor['cd_balance'];
                $request_ins["dclosing"] = $cedit_closing;
                $request_ins["dtype"] = "credit";
            } elseif ($creditor->role == 5) {
                $request_ins["uid"] = $creditor['id'];
                $request_ins["cd_opening"] = $creditor['cd_balance'];
                $request_ins["cd_closing"] = $cedit_closing;
                $request_ins["utype"] = "credit";
            }elseif ($creditor->role == 2) {
                $request_ins["aid"] = $creditor['id'];
                $request_ins["aopening"] = $creditor['cd_balance'];
                $request_ins["aclosing"] = $cedit_closing;
                $request_ins["atype"] = "credit";
            } else {
                $return['status']   =   0;
                $return['message']  =   "This transaction cannot be processed. Please try later.";
            }
            $cashData = CashTransaction::insert($request_ins);
            $insertId =DB::getPdo()->lastInsertId();
                            if($cashData){
                                $creditbalance =  DB::table('users')->where('id', $creditor->id)->update(['cd_balance' =>DB::raw('cd_balance+'.$request['amount']),'othercredit' =>DB::raw('othercredit+'.$request['amount'])]); 
                                if($creditbalance){
                                    $creditbalance =  DB::table('creditrequest')->where('id',$request['id'])->update(['status' =>1,'txnid' =>$insertId]); 
                                    $return['status']   =   1;
                                    $return['message']  =   "Transaction Successful";
                                    $return['newbalance']  =   $debt_closing;
                                }else {
                                    $return['status']   =   0;
                                    $return['message']  =   "This transaction cannot be processed. Please try later.";
                                }
                            }else {
                                $return['status']   =   0;
                                $return['message']  =   "Unable to update ledger";
                            }
    
                        }else {
                            $return['status']   =   0;
                            $return['message']  =   "Unable to debit account";
                        }
                    }else{
                       // $this->db->insert("warnings",array("message"=>"Unauthorised funding accessed by ".$debitor['username']));
                        $return['status']   =   0;
                        $return['message']  =   "Something went wrong. This transaction cannot be processed.";
                    }
                }else{
                    $return['status']   =   0;
                    $return['message']  =   "Unable to get certidor details";  
                 } 
                 
                }else{
                    $return['status']   =   0;
                    $return['message']  =   "Unable to get debitor details";    
                }
            }else{
                    $return['status']   =   0;
                    $return['message']  =   "Unable to get  Creditor or debitor";
            }
        }else{
            $return['status']   =   0;
            $return['message']  =   "Unable to get fund request";
        }
        return $return; 
       }
    public function approve(Request $request){
        $userdata = Auth::user();   
        if ($userdata) {
         if ($userdata && in_array($userdata->role, array(1,2))) { 
                 try {
                     $validated = Validator::make($request->all(), [
                         'id'       => 'required', 
                         "status"   => 'required',
                         "remarks"  => 'required'
                         
                     ]);
                     if ($validated->fails()) {
                         $message   = $this->validationResponse($validated->errors());
                         return $this->response('validatorerrors', $message);
                     } 
                    $cr = $this->getFundById($request->id); 
                    $req = Fund::getrequest($request->id);  
                    
                    if($req){
                    $remarks = $cr->referencenumber . "-" . $cr->requestremark. '-' .$request->remarks;
                    if (!empty($cr) && $cr->status == 2 && $request->status == "approved") { 
                        $requestdata = array(
                            "requestid" => $cr->id,
                            "remarks" => $remarks,
                            "comment" => $request->remarks,
                            "debitorid" => $this->GetuserId("ADMIN"),
                            "creditorid" => $cr->userid,
                            "ipaddress" => $_SERVER['REMOTE_ADDR'],
                            "ttype" => 0,
                            "processby" => $userdata->id
                        );
                       
                        if ($req['debitor']['cd_balance'] >= $req['request']['amount']) {
                          
                        
                         $response = $this->approverequest($requestdata); 
                         
                        if ($response['status']) {
                            $response = [

                                'responsecode' =>true,
                                'message' => "Fund request is " . $request->status . " successfully"
                            ];
                            return $this->response('success', $response); 
                        } else {
                            $response = [
                                'responsecode' =>false,
                                'errors' => "invalid!",
                                'message' => $response['message']
                            ];
                            return $this->response('notvalid', $response);  
                        }
                    }else{
                        $response = [
                            'responsecode' =>false,
                            'message' => "Insufficient fund in debitor account " . $req['debitor']['username'] . " Balance " . $req['debitor']['cd_balance']
                        ];
                        return $this->response('success', $response); 
                    }
                    }elseif ($request->status== "rejected") {
                        $requestdata = array("requestremark" => $remarks, "acomment" => $request->remarks, "debitor" =>self::GetuserId('ADMIN'), 'status' => 0);
                        $reject = $this->rejectrequest($requestdata , $cr->id);
                        if ($reject) {
                            $response = [
                                'responsecode' =>true,
                                'message' => "Fund request is " . $request->status . " successfully"
                            ];
                            return $this->response('success', $response); 
                        } else {
                            $response = [
                                'responsecode' =>false,
                                'errors' => "invalid!",
                                'message' => $response['message']
                            ];
                            return $this->response('notvalid', $response);  
                        }
                    }else {
                        $response = [
                            'responsecode' =>false,
                            'errors' => "invalid!",
                            'message' => "unable to find this request"
                        ];
                        return $this->response('notvalid', $response);  
                    } 
                }else{
                    $response = [
                        'responsecode' =>false,
                        'errors' => "invalid!",
                        'message' => 'Some Error Occured'
                    ];
                    return $this->response('notvalid', $response);  
                }

                 } catch (\Throwable $th) {
                     return $this->response('internalservererror', ['message' => $th->getMessage()]);
                 } 
         }else { 
             $response = [
                 'errors' => "invalid!",
                 'message' => "You are not allowed to approve this fund request"
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

    public function getFundingRequestDetail(Request $request){
        $userdata = Auth::user();   
        if ($userdata) {
         if ($userdata && in_array($userdata->role, array(1,2))) { 
                 try {
                     $validated = Validator::make($request->all(), [
                         'id'       => 'required',  
                         
                     ]);
                     if ($validated->fails()) {
                         $message   = $this->validationResponse($validated->errors());
                         return $this->response('validatorerrors', $message);
                     } 
                    $getSingleRequest = $this->getSingleRequest($request->id); 
                    if($getSingleRequest){
                        $response = [
                            'message' => "Data Found",
                            'data'    =>$getSingleRequest
                        ];
                        return $this->response('success', $response);
                    }else { 
                        $response = [
                            'errors' => "invalid!",
                            'message' => "No data Found!"
                        ];
                        return $this->response('notvalid', $response); 
                    } 
                 } catch (\Throwable $th) {
                     return $this->response('internalservererror', ['message' => $th->getMessage()]);
                 } 
         }else { 
             $response = [
                 'errors' => "invalid!",
                 'message' => "You are not allowed to approve this fund request"
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
    public function getpendingFund(Request $request){
        $userdata = Auth::user();   
        if ($userdata) {
             if ($userdata && in_array($userdata->role, array(1,2))) { 
                try{   
                        $startdate     = trim(strip_tags($request->startdate));
                        $enddate       = trim(strip_tags($request->enddate)); 
                        $status        = trim(strip_tags($request->status));
                        $userid        = trim(strip_tags($request->userid));
                        $start         = trim(strip_tags($request->start));
                        $length        = trim(strip_tags($request->length));
                        $order         = trim(strip_tags($request->order)); 
                        $search        = trim(strip_tags($request->search));  
                        $searchby      = trim(strip_tags($request->searchby));
                        $searchvalue   = trim(strip_tags($request->searchvalue));
                        if(empty($startdate) && empty($enddate)){
                            $startdate = $this->today;
                            $enddate   = $this->today;  
                        }
        
                    $order = 'DESC';
                    $query = DB::table('creditrequest');
                    $query->leftjoin('users', 'users.id', '=', 'creditrequest.userid');  
                    $query->leftjoin('companybank as tb2', 'tb2.id', '=', 'creditrequest.bankid');
                    $query->select('tb2.name',
                            'users.username', 
                            'users.phone', 
                            'creditrequest.id as reqid',
                            'users.cd_balance as current_balance',
                            'creditrequest.amount',
                            'creditrequest.depositeddate',
                            'creditrequest.requesttype',
                            'creditrequest.referencenumber',
                            'creditrequest.requestremark',
                            'creditrequest.image',
                            'creditrequest.created_at',
                            'creditrequest.status');
                
                    
                    
                        $query->where(function ($q) use ($startdate, $enddate) {
                            if (!empty($startdate) && !empty($enddate)) {
                                $q->whereRaw("date(tbl_creditrequest.created_at) between '{$startdate}' and '{$enddate}'"); 
                            }
                            return $q;
                        }); 

                    // $query->where('users.role',5);
                    if ($status != "" ) {
                        $query->where('creditrequest.status',$status);
                    }
                    if ($userid != "") {
                    $query->where('creditrequest.userid',$userid);
                    } 
                    if ($searchby  != "" && $searchvalue != "") {
                        if ($searchby == 'referencenumber') { 
                            $query->where('creditrequest.referencenumber', $searchvalue);
                        } else if ($searchby == 'amount') { 
                            $query->where('creditrequest.amount', $searchvalue);
                        } 
                        if ($searchby == 'username') {
                            $query->where('users.username', $searchvalue);
                        } 
                    } elseif ($searchvalue != "") {
                        $query->where(function ($query) use ($searchvalue) {
                            $query->where('creditrequest.reqid', 'like',  trim($searchvalue) . '%') 
                                ->orwhere('creditrequest.referencenumber', 'like',  trim($searchvalue) . '%')  
                                ->orwhere('creditrequest.username', 'like', trim($searchvalue) . '%');
                        });
                    }
                    
                    $totaldata = $query->get()->toArray();
                   
                    $recordsTotal = $query->count();
                    
                    if($order != ""){
                        $query->orderBy('creditrequest.id', $order);
                    }
                    if ($length != "" && $start !="") {
                        $data = $query->skip($start)->take($length)->get()->toArray();
                        $recordsFiltered = count($data);
                    }else{
                        $data = $query->get()->toArray();
                        $recordsFiltered = $query->count();
                    } 
                    $head           = HeaderTrait::txn_adminfund_header();
                   if(!empty($data)){
                       
                    $response = [
                        'message' => "Data Found",
                        'data'              => $data,
                          'header'            => $head,
                        'recordsFiltered'   => $recordsTotal,
                        'recordsTotal'      => $recordsFiltered,
                    ];
                    return $this->response('success', $response);
                        
                    }else{
                        $response = [
                            'errors' => "invalid!",
                            'message' => "No data Found!"
                        ];
                        return $this->response('notvalid', $response); 
                    }
                } catch (\Throwable $th) {
                    return $this->response('internalservererror', ['message' => $th->getMessage()]); 
                }
            }else { 
                $response = [
                    'errors' => "invalid!",
                    'message' => "You are not allowed to approve this fund request"
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

    public function getpendingById(Request $request){
        try {  
            $validated = Validator::make($request->all(), [ 
                'reqid' => 'required',
            ]);

            if ($validated->fails()) {
                $message   = $this->validationResponse($validated->errors());
                return $this->response('validatorerrors', $message);
            }
            $userdata = Auth::user();  
            if($userdata){
                $reqid =  $request->reqid;
                $info = Fund::getrequest($reqid);  
                    if($info){
                        $response = [
                            'message' => "All Pending Request!",
                            'data'              => $info, 
                        ];
                        return $this->response('success', $response); 
                    }else{
                        $response = [
                            'errors' => "invalid!",
                            'message' => "No data Found!"
                        ];
                        return $this->response('notvalid', $response);  
                    }
            }else{
                $response = [
                    'errors' => "invalid!",
                    'message' => "You are not Authorised!"
                ];
                return $this->response('notvalid', $response);  
            }     
                
             } catch (\Throwable $th) {
                return $this->response('internalservererror', ['message' => $th->getMessage()]); 
             } 
    }


    // ============================  User=====================//

    public function Getrequest(Request $request){ 
        try {   
            $userdata = Auth::user(); 
            if (in_array($userdata['role'], array(3,4,5))) {
                $userid        =   $userdata['id'];
                $startdate     = trim(strip_tags($request->startdate));
                $enddate       = trim(strip_tags($request->enddate));
                $searchvalue   = trim(strip_tags($request->searchvalue));
                $start         = trim(strip_tags($request->start));
                $status        = trim(strip_tags($request->status));
                $length        = trim(strip_tags($request->length));
                $order         = trim(strip_tags($request->order));
                $orderby       = trim(strip_tags($request->orderby)); 
                    $query = DB::table('creditrequest');
                    $query->leftjoin('companybank', 'creditrequest.bankid', '=', 'companybank.id');
                    $query->select('companybank.name as bankname', 'creditrequest.txnid', 'creditrequest.amount', 'creditrequest.depositeddate', 'creditrequest.created_at', 'creditrequest.status', 'creditrequest.requesttype', 'creditrequest.requestremark', 'creditrequest.id','creditrequest.image', 'creditrequest.comment', 'creditrequest.acomment', 'creditrequest.updated_at');
                    $query->where(function ($q) use ($startdate, $enddate) {
                        if (!empty($startdate) && !empty($enddate)) {
                            $q->whereRaw("date(creditrequest.created_at) between '{$startdate}' and '{$enddate}'");
                        }
                        return $q;
                    });
                     $query->where('creditrequest.userid', $userid);

                    if ($status != "" ) {
                        $query->where('creditrequest.status',$status);
                    }
                    if ($searchvalue != "") {
                        $query->where(function ($query) use ($searchvalue) {
                            $query->where('creditrequest.amount', 'like',  trim($searchvalue) . '%')
                                ->orwhere('creditrequest.txnid', 'like',  trim($searchvalue) . '%')
                                ->orwhere('companybank.name', 'like', trim($searchvalue) . '%')
                                ->orwhere('creditrequest.referencenumber', 'like', trim($searchvalue) . '%')
                                ->orwhere('creditrequest.comment', 'like', '%' .  trim($searchvalue) . '%')
                                ->orwhere('creditrequest.acomment', 'like',  trim($searchvalue) . '%');
                        });
                    }

                    
                    if ($order != "" && $orderby != "") {
                        $query->orderBy($orderby, $order);
                    } else {
                        $query->orderBy("creditrequest.id", "desc");
                    }
                    $recordsTotal = $query->count();

                    if (strtolower($length) == "all" && $start == 0) {
                        $data = $query->get()->toArray();
                        $recordsFiltered = $query->count();
                    } else if ($length != "" && $start != "") {
                        $data = $query->skip($start)->take($length)->get()->toArray();
                        $recordsFiltered = count($data);
                    } else {
                        $data = $query->get()->toArray();
                        $recordsFiltered = $query->count();
                    }
                    $totalamt =0;
                    foreach($data as $key=>$datum){
                    // if($datum->status){
                    //     $data[$key]->status =   $this->status_array[$datum->status];
                    // }
                    
                    $totalamt +=  $datum->amount;
                    if($datum->status){
                        $dateTime = new DateTime($datum->depositeddate, new DateTimeZone('Asia/Kolkata'));  
                        
                        $data[$key]->depositeddate =   $dateTime->format("d-m-Y"); 
                       
                    } 
                }
                    $head           = HEADERTrait::getrequest_Fund();
                    $response = [
                        'message' => "Success",
                        'data'              => $data, 
                        'header'            => $head,
                        'recordsFiltered'   => $recordsFiltered,
                        'recordsTotal'      => $recordsTotal,
                        'totalamt'          =>$totalamt,
                    ];
                    return $this->response('success', $response);  
            } else {
                $response = [
                    'errors' => "invalid!",
                    'message' => "You Don't have permission to use this!"
                ];
                return $this->response('notvalid', $response);   
            }
         } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]); 
         } 
    }
}