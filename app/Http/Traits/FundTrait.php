<?php

namespace App\Http\Traits;

use GuzzleHttp\Client;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\CashTransaction;
use Illuminate\Support\Facades\DB;
use App\Models\UserPasswordDetails as UserPassword;
use App\Models\Creditrequest;
use App\Models\CompanyBank;
trait FundTrait
{
    function addfundadmin($data) {
        $request = array("userid" => $data['aid'], "debitor" => $data['sid'], "amount" => $data['amount'], "credit" => $data['credit'], "status" => 1, "comment" => $data['narration'], "processby" => $data['sid'], "addeddate" => date('Y-m-d'),"depositeddate" => date('Y-m-d'));
        $requestfund = Creditrequest::insert($request);  
        $insertId =DB::getPdo()->lastInsertId();
        if ($requestfund) {
            $s =  DB::table('users')->where('id',20231001)->update(['cd_balance' =>DB::raw('cd_balance-'.$data['amount'])]); 
            $a =  DB::table('users')->where('id', 20231002)->update(['cd_balance' =>DB::raw('cd_balance+'.$data['amount'])]); 
            $updateCredit =   DB::table('users')->where('id', 20231002)->update(['credit' =>DB::raw('credit+'.$data['amount'])]);  
            if ($s && $a) {
                $transaction = array("sid" => $data['sid'], "aid" => $data['aid'], "amount" => $data['amount'], "aopening" => $data['aopening'], "aclosing" => $data['aclosing'], "sopening" => $data['sopening'], "sclosing" => $data['sclosing'], "stype" => $data['stype'], "atype" => $data['atype'], "narration" => $data['narration'], "ipaddress" => $data['ipaddress'], "ttype" => $data['ttype'], "addeddate" => date('Y-m-d'));
                $txnData = CashTransaction::insert($transaction);
                if ($txnData) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
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
                        if($cashData){
                        $creditbalance =  DB::table('users')->where('id', $creditor->id)->update(['cd_balance' =>DB::raw('cd_balance+'.$request['amount']),'othercredit' =>DB::raw('othercredit+'.$request['amount'])]); 
                            if($creditbalance){
                                $creditbalance =  DB::table('creditrequest')->where('id',$request['id'])->update(['status' =>1]); 
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

    public static function getclosing($where,$balance,$columname,$usertype){
        $query = DB::table('transaction_cashdeposit');
        $query->select($columname);
        $query->where($where); 
        if($usertype==5){
            $query->whereIn("ttype",self::wttype('main'));  
        }else{
            $query->whereIn("ttype",self::wttype('partner'));  
        } 
        $query->orderBy('id', "desc"); 
        $query->take(1); 
        $return =   $query->get()->toArray();

        if(isset($return[0])){
            if(round($return[0]->$columname) == round($balance)){
                return true;
            }else{
                return false;
            }
        }else{
            return true;
        }
    } 
    
    public static function wttype($type)
    {
        $all    =    array(
            "main"=>array(0,1,3,4,5,6,7,8,9,11,12,13,14,23,25),
            "cash"=>array(7,11,13,100,101,102,103,104),
            "partner"=>array(0,1,10));   //100-cahwithdraw -101- aadharpay - 102- ministatement - 103- ministatement - 104- matm 14-fastag 23- CD;
        return $all[$type]; 
    } 

    function getFundById($reqid){
        $data = Creditrequest::select("*")->where("id", $reqid)->first();
        return $data;
    }
    function approverequest($reqdata){
        $creditrequest = Creditrequest::select("*")->where("userid", $reqdata['creditorid'])->where("status", 2)->where("id", $reqdata['requestid'])->first();
        if (!empty($creditrequest)) {
            $amount = $creditrequest->amount;
            $where_array = "";
            $closing = "";

             //debitor details  
            $debitor = User::select("*")->where(array('id'=>$reqdata['debitorid']))->first();
             //creditor details
            $creditor = User::select("*")->where(array('id'=>$reqdata['creditorid']))->first();

            $copening = $creditor->cd_balance;
            $cclosing = $creditor->cd_balance + $amount;
            $credit = $creditor->credit + $amount;
  
            $naration = "Balance transfer amount " . $amount . " from " . $debitor->username . " to " . $creditor->username;
            $transaction = array("amount" => $amount, "narration" => $naration, "remarks" => $reqdata['remarks'], "ipaddress" => $reqdata['ipaddress'], "ttype" => 0, "addeddate" =>self::GetuserId('DATE'), "sid" =>self::GetuserId('SUPERADMIN'));
            
            //creditor transaction
            if ($creditor->role == 5) {
                //credit to retailer
                $transaction['uid'] = $creditor->id;
                $transaction['utype'] = "credit";
                $transaction['cd_opening'] = $copening;
                $transaction['cd_closing'] = $cclosing;
                $where_array = array('uid' => $creditor->id);
                $closing = "cd_closing";
            } elseif ($creditor->role == 4) {
                $transaction['did'] = $creditor->id;
                $transaction['dtype'] = "credit";
                $transaction['dopening'] = $copening;
                $transaction['dclosing'] = $cclosing;
                $where_array = array('did' => $creditor->id);
                $closing = "dclosing";
            }elseif ($creditor->usertype == 3) {
                $transaction['sdid'] = $creditor->id;
                $transaction['sdtype'] = "credit";
                $transaction['sdopening'] = $copening;
                $transaction['sdclosing'] = $cclosing;
                $where_array = array('sdid' => $creditor->id);
                $closing = "sdclosing";
            } else {
                return array("status" => false, "message" => "Creditor details is not valid");
            }

            //Debitor will always be admin 
            if ($debitor->role == 2) {
                //debit from accounts
                $transaction['aid'] = $debitor->id;
                $transaction['atype'] = "debit";
                $transaction['aopening'] = $debitor->cd_balance;
                $transaction['aclosing'] = $debitor->cd_balance - $amount;
            } else {
                return array("status" => false, "message" => "Debitor details is not valid");
            } 
           
            $cashclosingCrediter = self::getclosing($where_array,$creditor->cd_balance,$closing,$creditor->role);
            if($cashclosingCrediter){
                $cashData = CashTransaction::insert($transaction); 
                if ($cashData) {
                    $last_txn_id_of_fund =DB::getPdo()->lastInsertId(); 
                    //updating debitor fund 
                    $updatingdebitor =  DB::table('users')->where('id', $debitor->id)->update(['cd_balance' =>DB::raw('cd_balance-'.$amount)]); 
                    //updating Creditor fund
                    $updatingCreditor =  DB::table('users')->where('id', $creditor->id)->update(['cd_balance' =>DB::raw('cd_balance+'.$amount),'credit' =>DB::raw('credit+'.$amount)]); 
                    
                        $update = array(
                                "txnid" => $last_txn_id_of_fund,
                                "status" => 1,
                                "authorizeby" => $reqdata['processby'],
                                "authdate" => self::GetuserId("DATE"),
                                "credit" => $credit,
                                "acomment" => $reqdata['comment'],
                                "debitor" => $reqdata['debitorid']
                            );
                        $creditbalance =  DB::table('creditrequest')->where('id',$creditrequest->id)->update($update);  
                        if ($creditrequest->bankid != 0) { 
                            $bankdetails = CompanyBank::select("*")->where("id", $creditrequest->bankid)->first();

                            $bopening = $bankdetails->balance;
                            $bclosing = $bankdetails->balance + $amount;

                            $updatingCreditor =  DB::table('companybank')->where('id', $bankdetails->id)->update(['balance' =>DB::raw('balance+'.$amount)]); 
                            // if (in_array($creditrequest->requesttype, array(1, 2, 3))) {
                            //     $type = 0;
                            // } else {
                            //     $type = 1;
                            // }

                        }
                    return array("status" => true, "message" => "Successfull", "opening" => $copening, "closing" => $cclosing);
                } else {
                    return array("status" => false, "message" => "Unable to credit account 2");
                }
            }else{
                return array("status" => false, "message" => "Creditor wallet mis-matched");
             }
            

        } else {
            return array("status" => false, "message" => "Unable to get fund request");
        }
    }

    public static function GetuserId($type)
    {
        $all = array(
            "ADMIN"=>20231002,
            "SUPERADMIN"=>20231001,
            "DATE"=>date('Y-m-d')
        );  
        return $all[$type]; 
    } 

    function rejectrequest($reqdata , $id){
       return DB::table('creditrequest')->where('id',$id)->update($reqdata);  
    }

    function getSingleRequest($id){
        $info   = CreditRequest::select(
            'companybank.name',
                    'users.username', 
                    'users.phone', 
                    'creditrequest.id as reqid',
                    'users.balance as current_balance',
                    'creditrequest.amount',
                    'creditrequest.depositeddate',
                    'creditrequest.requesttype',
                    'creditrequest.referencenumber',
                    'creditrequest.requestremark',
                    'creditrequest.image',
                    'creditrequest.addeddate',
                    'creditrequest.status',
            )
         ->leftJoin('users','users.id', '=', 'creditrequest.userid')
         ->leftJoin('companybank','companybank.id', '=', 'creditrequest.bankid')
          ->where("creditrequest.id",$id)
          ->orderBy("creditrequest.addeddate", "desc")
        //  ->where('creditrequest.status',3) 
         ->get();
        return $info;  
     }

    
}