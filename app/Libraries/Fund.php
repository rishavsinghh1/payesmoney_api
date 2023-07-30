<?php
namespace App\Libraries;   
use App\Models\User;
use App\Models\Creditrequest;
use App\Models\CompanyBank;
use App\Models\CashTransaction;
use App\Libraries\Common\User as Userlib;  
class Fund{ 
    public static function getrequest($reqid){
        $creditrequest['request'] = self::getcreditrequest($reqid);
       
        if($creditrequest['request']){
             $creditrequest['lastapproverequest'] = self::getcreditordetails($creditrequest['request']['userid'],$creditrequest['request']['amount']);
        $debiterrow = User::select('id','username','cd_balance')
        ->where('status',1)
        ->where('id',Userlib::$aid)
        ->first(); 
        $creditrequest['debitor'] = $debiterrow;
        $similarrequest = array("amount"=>$creditrequest['request']['amount'],"creditorid"=>$creditrequest['request']['userid']);
        $creditrequest['similar'] = self::getsimillarrequest($similarrequest);
        return $creditrequest;    
        }else{
            return false;  
              
        }
         
    }
    public static function getcreditrequest($reqid){
         
        $info   = CreditRequest::select(
                     'companybank.name',
                     'users.username', 
                     'users.phone', 
                     'creditrequest.userid',
                     'creditrequest.id as reqid',
                     'users.cd_balance as current_balance',
                     'creditrequest.amount',
                     'creditrequest.depositeddate',
                     'creditrequest.requesttype',
                     'creditrequest.referencenumber',
                     'creditrequest.requestremark',
                     'creditrequest.image',
                     'creditrequest.created_at'
              )
                  ->leftJoin('users','users.id', '=', 'creditrequest.userid')
                  ->leftJoin('companybank','companybank.id', '=', 'creditrequest.bankid') 
                  ->where('creditrequest.status',2) 
                  ->where('creditrequest.id',$reqid)
                  ->first();  
             return $info;      
     }

         
    public static function getcreditordetails($userid,$type,$amount=""){
        $row = CashTransaction::select(
                   'id as txnid',
                   'amount', 
                   'addeddate', 
                   'narration',
                   'remarks')
               ->where('uid',$userid)
               ->where('ttype',0)
               ->where('aid',Userlib::$aid)
               ->orderBy("id", "desc")
               ->limit(100)
               ->get()->toArray(); 
     
       $return_array   =   array();
       $i  =   0;
       foreach($row    as  $result){
           if($result['amount'] == $amount){
              $return_array[$i++]  =   $result; 
           }
       }
       return  $return_array;   
    }
    public static function getsimillarrequest($request){
        $dateonly =   date("Y-m-d");
        $simillarrequest   = CreditRequest::select('id')
                            ->where('userid',$request['creditorid']) 
                            ->where('debitor',Userlib::$aid) 
                            ->where('status',1) 
                            ->where('amount',$request['amount'])     
                            ->whereDate('updated_at','=', $dateonly)
                            ->get()->toArray();  
            return $simillarrequest;
        if(empty($simillarrequest)){
            return false;
        }else{
            return true;
        }
    }
}