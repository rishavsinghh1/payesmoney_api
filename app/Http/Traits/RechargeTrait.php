<?php

namespace App\Http\Traits;

use GuzzleHttp\Client;
use DateTime;
use Illuminate\Http\Request;
use App\Models\CommissionTemplate;
use App\Models\CommissionModel;
use App\Models\Recharge;
use App\Models\CashTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Hash; 
use Illuminate\Support\Facades\DB;

trait RechargeTrait
{
    public static function process($reqData){
        $txnData    =   array();
        $return     =   array();
        $txnData['userid']      =   $reqData['uid'];
        $txnData['operatorname']=   $reqData['operatorname'];
        $txnData['canumber']    =   $reqData['canumber'];
        $txnData['amount']      =   $reqData['amount'];
        $txnData['comm']        =   $reqData['comm'];
        $txnData['dcomm']       =   $reqData['dcomm']; 
        $txnData['sdcomm']      =   $reqData['sdcomm']; 
        $txnData['status']      =   $reqData['status'];
        $txnData['apitype']     =   $reqData['apitype'];
        $txnData['refid']       =   $reqData['refid'];
        $txnData['addeddate']   =   date('Y-m-d');

        $stmtData['sid']        =   self::GetuserId('SUPERADMIN');
        $stmtData['uid']        =   $reqData['uid'];
        $stmtData['did']        =   $reqData['did']; 
        $stmtData['sdid']       =   $reqData['sdid'];
        $stmtData['amount']     =   $reqData['amount'];
        $stmtData['addeddate']  =   date('Y-m-d'); 
        $stmtData['comm']     	=   $reqData['comm'];
        $stmtData['dcomm']      =   $reqData['dcomm']; 
        $stmtData['sdcomm']     =   $reqData['sdcomm'];
        $stmtData['utype']          =   'debit';
        $stmtData['sdtype']         =   'credit';
        $stmtData['dtype']          =   'credit';  
        $stmtData['narration']      =   "Recharge of amount Rs.".$reqData['amount']." for CA Number ".$reqData['canumber'];
        $stmtData['ttype']          =   6;
        $stmtData['ipaddress']      =   $reqData['ipaddress'];
        
        $agentcl	=	$reqData['amount']-($reqData['comm']);
        $wttype = self::wttype('main'); 
        DB::unprepared("LOCK TABLES tbl_transaction_cashdeposit as t READ,tbl_transaction_cashdeposit WRITE,tbl_users as u READ, tbl_users WRITE,tbl_recharge as r READ, tbl_recharge  WRITE");
        $query = DB::select(("SELECT SQL_NO_CACHE u.id, u.cd_balance, u.username,t.cd_closing from tbl_transaction_cashdeposit as t left JOIN tbl_users as u on t.uid=u.id where t.uid=".$reqData['uid']." and t.ttype in(".implode(",",$wttype).")  ORDER by t.id desc limit 1"));
        $u_data =  $query[0];
         $stmtData['cd_opening']   =   $u_data->cd_balance;
         $stmtData['cd_closing']   =   ($stmtData['cd_opening']-$agentcl);
         if(count($query)!=0 && $u_data->cd_balance>=$agentcl){
            if(self::roundval($u_data->cd_closing) == self::roundval($u_data->cd_balance)){
                $cd_balance = $u_data->cd_balance - $agentcl;
                $userupdate = ['cd_balance' => $cd_balance];
                $isupdate = User::where('id', $reqData['uid'])->update($userupdate);
                if($isupdate){
                    $insertedid = CashTransaction::insertGetId($stmtData);
                    if($insertedid){
                        $txnData["txnid"] = $insertedid;
                        $insTxn = Recharge::insertGetId($txnData);
                        if ($insTxn) { 
                            $return = array("status" => 1,"txnno"=>$insertedid,"orderid"=>$insTxn, "message" => "Transaction Successfull");
                        }else{
                            $return = array("status" => 0, "message" => "Unable to insert transaction.");
                        } 
                    }else{
                        $return		=	array("status"=>0,"message"=>"Unable to update ledger");
                    }
                }else{
                    $return		=	array("status"=>0,"message"=>"Unable to update balance");
                }
            }else{
                $return		=	array("status"=>0,"message"=>"Transaction cannot process. API Partner wallet mis-matched");
            }
         }else{
            $return		=	array("status"=>0,"message"=>"Do not have sufficient balance.Please Request Fund.");
         }   
         DB::unprepared("UNLOCK TABLES"); 
        return $return;
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
    public static function roundval($amount){
    	$b	=	explode(".",$amount);
    	if(isset($b[1])){
        	$c	= 	substr($b[1],0,2);
        	
        	return $b[0] . "." .$c;
    	}else{
    	    return $amount;
    	}	
    }
    public static function wttype($type)
    {
        $all    =   array("main"=>array(0,1,3,4,5,6,7,8,9,11,12,13,14,23,25),"cash"=>array(7,11,13,100,101,102,103,104),"partner"=>array(0,1,10)); 
        return $all[$type]; 
    }
    public static function credit($req){ 
        
        $return     =   array();
        
        if(isset($req['sid'])){ $stmtData['sid']=$req['sid']; }
        if(isset($req['aid'])){ $stmtData['aid']=$req['aid']; }
        if(isset($req['uid'])){ $stmtData['uid']=$req['uid']; }
        if(isset($req['amount'])){ $stmtData['amount']=$req['amount']; }
        if(isset($req['comm'])){ $stmtData['comm']=$req['comm']; }
         if(isset($req['dcomm'])){ $stmtData['dcomm']=$req['dcomm']; } 

        if(isset($req['sdcomm'])){ $stmtData['sdcomm']=$req['sdcomm']; }
        if(isset($req['utype'])){ $stmtData['utype']=$req['utype']; }
        if(isset($req['stype'])){ $stmtData['stype']=$req['stype']; }
        if(isset($req['atype'])){ $stmtData['atype']=$req['atype']; }
        if(isset($req['narration'])){ $stmtData['narration']=$req['narration']; }
        if(isset($req['remarks'])){ $stmtData['remarks']=$req['remarks']; }
        if(isset($req['ttype'])){ $stmtData['ttype']=$req['ttype']; }
        if(isset($req['refunded'])){ $stmtData['refunded']=$req['refunded']; } 
        $stmtData['addeddate']= date('Y-m-d');
        if(isset($req['ipaddress'])){ $stmtData['ipaddress']=$req['ipaddress']; } 
        if(isset($req['tds'])){ $stmtData['tds']=$req['tds']; }
        if(isset($req['gst'])){ $stmtData['gst']=$req['gst']; }
        if(isset($req['field1'])){ $stmtData['field1']=$req['field1']; }
        if(isset($req['field2'])){ $stmtData['field2']=$req['field2']; }
        if(isset($req['field3'])){ $stmtData['field3']=$req['field3']; }
        if(isset($req['field4'])){ $stmtData['field4']=$req['field4']; }
        if(isset($req['field5'])){ $stmtData['field5']=$req['field5']; }
        if(isset($req['field6'])){ $stmtData['field6']=$req['field6']; }
        if(isset($req['field7'])){ $stmtData['field7']=$req['field7']; }
        if(isset($req['field8'])){ $stmtData['field8']=$req['field8']; }
        if(isset($req['field9'])){ $stmtData['field9']=$req['field9']; }
        if(isset($req['field10'])){ $stmtData['field10']=$req['field10']; }
        
        
        $agentcl	=	$req['creditamount']; 
        $wttype = self::wttype('main'); 
        DB::unprepared("LOCK TABLES tbl_transaction_cashdeposit as t READ,tbl_transaction_cashdeposit WRITE,tbl_users as u READ, tbl_users WRITE,tbl_recharge as r READ, tbl_recharge  WRITE");
        $query = DB::select(("SELECT SQL_NO_CACHE u.id, u.cd_balance, u.username,t.cd_closing from tbl_transaction_cashdeposit as t left JOIN tbl_users as u on t.uid=u.id where t.uid=".$req['uid']." and t.ttype in(".implode(",",$wttype).")  ORDER by t.id desc limit 1"));
        $u_data =  $query[0];
         $stmtData['cd_opening']   =   $u_data->cd_balance;
         $stmtData['cd_closing']   =   ($stmtData['cd_opening']+$agentcl);


 
        if(count($query)!=0 ){
            if(self::roundval($u_data->cd_closing) == self::roundval($u_data->cd_balance)){
                $balance = $u_data->cd_balance + $agentcl;
                $userupdate = ['cd_balance' => $balance];
                $isupdate = User::where('id', $req['uid'])->update($userupdate);
                if($isupdate){
                    $insertedid = CashTransaction::insertGetId($stmtData);
                    if($insertedid){
                        $return = array("status" => 1,"txnno"=>$insertedid, "message" => "Transaction Successfull");
                    }else{
                        $return		=	array("status"=>0,"message"=>"Unable to update ledger");
                    }
                }else{
                    $return		=	array("status"=>0,"message"=>"Unable to update API partner balance");
                }
            }else{
                $return		=	array("status"=>0,"message"=>"Transaction cannot process. API Partner wallet mis-matched");
            }
        }else{
            $return		=	array("status"=>0,"message"=>"Do not have sufficient balance.Please Request Fund.");
        }    
        DB::unprepared("UNLOCK TABLES");
        return $return;
        
    }
}