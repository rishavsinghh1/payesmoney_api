<?php

namespace App\Http\Traits;

use GuzzleHttp\Client;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash; 
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\CashTransaction;
trait CommissionTrait
{
   public static function signlequery($table,$type){
    //$str = ['id','name','type','commission','status'];
    //$str = implode(',', $str);
     
        $query = DB::table($table);
        $query->select('id','name','type','commission','status');  
        $query->where($type); 
        $qr = $query->get()->toArray();
        $records    = $qr;
        return $records; 
   }

   public static function signlequery_temp($table,$type){
          $query = DB::table($table);
          $query->select('id','tempid','type','userid');  
          $query->where($type); 
          $qr = $query->get()->toArray();
          $records    = $qr;
          return $records; 
   }
   public static function getuser($search){
      $where = $search;
      unset($where['username']);
       $query = DB::table('users');
      $query->select(
                 'id','username','firmname','pone','fullname','role as usertype',
                 DB::raw('CONCAT(fullname,"|",username,"|",firmname) as userdetails')
                ); 
       $query->where($where);
       $query  ->where("role",5);
      if(!empty($search) && $search['username'] != ''){ 
         
         $query->where(function ($query) use ($search) {
            $query->where('username', 'like',  trim($search['username']) . '%')
                ->orwhere('firmname', 'like', trim($search['username']) . '%')
                ->orwhere('fullname', 'like',  trim($search['username']) . '%');
        }); 
      } 
      return $query->get()->toArray();
        
   }
   public static function Debitsupercomm($reqData){
    $return     =   array();
    $debitor    =  User::select("*")->where("id",$reqData['id'])->first();
    
    if(!empty($debitor)){ 
       if(self::getclosing(array("sdid"=>$debitor->id),$debitor->cd_balance,"sdclosing",$debitor->role)){
          $debt_closing = $debitor->cd_balance - $reqData['amount'];
           //credit Commission details  
          
           $updatedb =  DB::table('users')->where('id', $debitor->id)->update(['cd_balance' =>DB::raw('cd_balance-'.$reqData['amount'])]);  
         
           if($updatedb){
                   $transaction = array(
                      "sdid"      => $debitor->id,
                      "amount"    => $reqData['amount'],
                      "sdcomm"    => 0,
                      "tds"       => $reqData['tds'],
                      "sdtype"    => "debit",
                      "sdopening" => $debitor->cd_balance,
                      "sdclosing" => $debt_closing,
                      "narration" => $reqData['narration'],
                      "status"    => 1,
                      "refunded"  => 1,
                      "addeddate" => $reqData['addeddate'],
                      "ttype"     => 11,
                );
                $last_txn_id_of_sd_comm = CashTransaction::insertGetId($transaction); 
                if ($last_txn_id_of_sd_comm) {
                   $return['status']   =   1;
                   $return['message']  =   "This Super-Distributor transaction processed.";
                } else {
                      $return['status']   =   0;
                      $return['message']  =   "This transaction cannot be processed. Please try later.";
                }
           }else {
             $return['status']   =   0;
             $return['message']  =   "Unable to update wallet transaction.";
          }  
       }else{
         // $this->db->insert("warnings",array("message"=>"Unauthorised funding accessed by ".$debitor->username));
          $return['status']   =   0;
          $return['message']  =   "Transaction cannot process. API Partner wallet mis-matched";
      }
    }else{
          $return['status']   =   0;
          $return['message']  =   "This user cannot be fund. Please try later.";
      }
      return $return;
   }
   public static function DebitDistcomm($reqData){
    $return     =   array();
    $debitor    =  User::select("*")->where("id",$reqData['id'])->first();
    
    if(!empty($debitor)){ 
       
       if(self::getclosing(array("did"=>$debitor->id),$debitor->cd_balance,"dclosing",$debitor->role)){
          $debt_closing = $debitor->cd_balance - $reqData['amount'];
           //credit Commission details  
          
           $updatedb =  DB::table('users')->where('id', $debitor->id)->update(['cd_balance' =>DB::raw('cd_balance-'.$reqData['amount'])]);  
         
           if($updatedb){
                   $transaction = array(
                      "did"      => $debitor->id,
                      "amount"    => $reqData['amount'],
                      "sdcomm"    => 0,
                      "tds"       => $reqData['tds'],
                      "dtype"    => "debit",
                      "dopening" => $debitor->cd_balance,
                      "dclosing" => $debt_closing,
                      "narration" => $reqData['narration'],
                      "status"    => 1,
                      "refunded"  => 1,
                      "addeddate" => $reqData['addeddate'],
                      "ttype"     => 11,
                );
                $last_txn_id_of_sd_comm = CashTransaction::insertGetId($transaction); 
                if ($last_txn_id_of_sd_comm) {
                   $return['status']   =   1;
                   $return['message']  =   "This Distributor transaction processed.";
                } else {
                      $return['status']   =   0;
                      $return['message']  =   "This transaction cannot be processed. Please try later.";
                }
           }else {
             $return['status']   =   0;
             $return['message']  =   "Unable to update wallet transaction.";
          }  
       }else{
         // $this->db->insert("warnings",array("message"=>"Unauthorised funding accessed by ".$debitor->username));
          $return['status']   =   0;
          $return['message']  =   "Transaction cannot process. API Partner wallet mis-matched";
      }
    }else{
          $return['status']   =   0;
          $return['message']  =   "This user cannot be fund. Please try later.";
      }
      return $return;
   }
   public static function supercomm($reqData){
      $return     =   array();
      $debitor    =  User::select("*")->where("id",$reqData['id'])->first();
      if(!empty($debitor)){
         if(self::getclosing(array("sdid"=>$debitor->id),$debitor->cd_balance,"sdclosing",$debitor->role)){
            $debt_closing = $debitor->cd_balance + $reqData['amount'];
             //credit Commission details  
             $updatedb =  DB::table('users')->where('id', $debitor->id)->update(['cd_balance' =>DB::raw('cd_balance+'.$reqData['amount'])]);  
           
             if($updatedb){
                     $transaction = array(
                        "sdid"      => $debitor->id,
                        "amount"    => $reqData['amount'],
                        "sdcomm"    => 0,
                        "tds"       => $reqData['tds'],
                        "sdtype"    => "credit",
                        "sdopening" => $debitor->cd_balance,
                        "sdclosing" => $debt_closing,
                        "narration" => $reqData['narration'],
                        "status"    => 1,
                        "refunded"  => 0,
                        "addeddate" => $reqData['addeddate'],
                        "ttype"     => 10,
                  );
                  $last_txn_id_of_sd_comm = CashTransaction::insertGetId($transaction); 
                  if ($last_txn_id_of_sd_comm) {
                     $return['status']   =   1;
                     $return['message']  =   "This transaction processed.";
                  } else {
                        $return['status']   =   0;
                        $return['message']  =   "This transaction cannot be processed. Please try later.";
                  }
             }else {
               $return['status']   =   0;
               $return['message']  =   "Unable to update wallet transaction.";
            }  
         }else{
           // $this->db->insert("warnings",array("message"=>"Unauthorised funding accessed by ".$debitor->username));
            $return['status']   =   0;
            $return['message']  =   "Transaction cannot process. API Partner wallet mis-matched";
        }
      }else{
            $return['status']   =   0;
            $return['message']  =   "This user cannot be fund. Please try later.";
        }
        return $return;
   } 
   public static function distributorcomm($reqData){
    $return     =   array();
    $debitor    =  User::select("*")->where("id",$reqData['id'])->first();
    if(!empty($debitor)){
       if(self::getclosing(array("did"=>$debitor->id),$debitor->cd_balance,"dclosing",$debitor->role)){
          $debt_closing = $debitor->cd_balance + $reqData['amount'];
           //credit Commission details  
           $updatedb =  DB::table('users')->where('id', $debitor->id)->update(['cd_balance' =>DB::raw('cd_balance+'.$reqData['amount'])]);  
           if($updatedb){
                    $transaction = array(
                        "did"       => $debitor->id,
                        "amount"    => $reqData['amount'],
                        "dcomm"     => 0,
                        "tds"       => $reqData['tds'],
                        "dtype"     => "credit",
                        "dopening"  => $debitor->cd_balance,
                        "dclosing"  => $debt_closing,
                        "narration" => $reqData['narration'],
                        "status"    => 1,
                        "refunded"  => 0,
                        "addeddate" => $reqData['addeddate'],
                        "ttype"     => 10,
                    ); 
                    $last_txn_id_of_sd_comm = CashTransaction::insertGetId($transaction); 
                    if ($last_txn_id_of_sd_comm) {
                    $return['status']   =   1;
                    $return['message']  =   "This transaction processed.";
                    } else {
                        $return['status']   =   0;
                        $return['message']  =   "This transaction cannot be processed. Please try later.";
                    }
           }else {
             $return['status']   =   0;
             $return['message']  =   "Unable to update wallet transaction.";
          }  
       }else{
          //$this->db->insert("warnings",array("message"=>"Unauthorised funding accessed by ".$debitor->username));
          $return['status']   =   0;
          $return['message']  =   "Transaction cannot process. API Partner wallet mis-matched";
      }
    }else{
          $return['status']   =   0;
          $return['message']  =   "This user cannot be fund. Please try later.";
      }
      return $return;
    }

    public static function getclosing($where,$balance,$columname,$usertype){ 
      $query = DB::table('transaction_cashdeposit');
      $query->select($columname);
      $query->where($where); 
      $query->where($columname, "!=", '""');
      
     
      if($usertype==5){
          $query->whereIn("ttype",self::walletttype('main'));  
      }else{
          $query->whereIn("ttype",self::walletttype('main')); 
          
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
  public static function walletttype($type){
      $all    =    array(
          "main"=>array(0,1,3,4,5,6,7,8,9,11,12,13,14,23,25),
          "cash"=>array(7,11,13,100,101,102,103,104),
          "partner"=>array(0,1,10,200,6));   //100-cahwithdraw -101- aadharpay - 102- ministatement - 103- ministatement - 104- matm 14-fastag 23- CD;
      return $all[$type]; 
  } 
}