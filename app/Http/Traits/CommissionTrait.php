<?php

namespace App\Http\Traits;

use GuzzleHttp\Client;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash; 
use Illuminate\Support\Facades\DB;

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
}