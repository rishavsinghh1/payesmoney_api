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
                $cedit_closing   =   $creditor->cd_balance + $request['amount'];
                /// to be wriote
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
}