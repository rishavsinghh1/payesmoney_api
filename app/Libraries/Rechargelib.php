<?php
namespace App\Libraries;
use App\Libraries\Common\Logs;   
use Carbon\Carbon;
class Rechargelib{
    public static $url = "https://uat.bestapi.in/web-api/api/v1/";
    public static $liveurl = " https://api.bestapi.in/api/v1/";
   
    public static $authkey = "ZDI1M2Q5NGIwMDBmNTdiZDJiODNjM2FlNGE1NTc0NWE="; 
    public static $required = array('error_code','errorMessage');

    public static function writelog($type,$req,$dirname){
        if(is_array($req)){
	        $array      =   json_encode($req, TRUE);    
	    }else{
	        $array      =   $req;
	    }
        Logs::writelogs(array("dir"=>$dirname,"type"=>$type,"data"=>$array));
    }
    
    public static function response($response){ 
        $res =  json_decode($response, TRUE);
        if(count($res) <= 0){
            return false;
        } 
        if (count(array_intersect_key(array_flip(static::$required), $res)) > 0 ) { 
            return false;
        }
        return $res;
    }

    public static function hitting($request){
        $num    =   time();
         self::writelog("REQUEST".$num,$request,$request['apiname']);
         $rechargeinsert =[
             'request'=>$request,
             'method'=>$request['apiname']
             ]; 
         $curl = curl_init();
         curl_setopt_array($curl, [
           CURLOPT_URL => $request['url'],
           CURLOPT_RETURNTRANSFER => true,
           CURLOPT_ENCODING => "",
           CURLOPT_MAXREDIRS => 10,
           CURLOPT_TIMEOUT => 15,
           CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
           CURLOPT_CUSTOMREQUEST => $request['method'],
           CURLOPT_POSTFIELDS => json_encode($request['parameter']),
           CURLOPT_HTTPHEADER => [  
             "Apikey: " . static::$authkey,
             "accept: application/json",
             "content-type: application/json"
           ],
         ]);
         $response = curl_exec($curl); 
        
         if(curl_errno($curl)){
             $response   =   self::response(json_encode(array("errorCode"=>"PayesMoney-001","error_code"=>curl_errno($curl),"message"=>curl_error($curl),"errorMessage"=>"Unable to get response please try again later"))); 
         }else if(isset($response) && $response != ""){
               $response   =   self::response($response);
         }else{
             $response   =   self::response(json_encode(array("errorCode"=>"PayesMoney-001","error_code"=>90,"message"=>"empty curl response","errorMessage"=>"Unable to get response please try again later"))); 
         }
         self::writelog("RESPONSE".$num,$response,$request['apiname']);
         return $response;
     }

     public static function dorecharge($request){ 
        $data = [
            'method' => $request['method'],
            'apiname'=> $request['apiname'],
            'url'    => static::$url.'recharge',

            'parameter' => [ 
                    'mobileno'   => $request['canumber'],
                    'amount'     => $request['amount'],
                    'operator'   => $request['operator'],
                    'reqId'      => $request['referenceid']            
            ]
        ];
       return self::hitting($data);
    }

    public static function hittingwithoutkey($request){ 
        $num    =   time();
         self::writelog("REQUEST".$num,$request,$request['apiname']); 
         $curl = curl_init();
         curl_setopt_array($curl, [
           CURLOPT_URL => 'https://api.bestapi.in/api/v1/getop',
           CURLOPT_RETURNTRANSFER => true,
           CURLOPT_ENCODING => "",
           CURLOPT_MAXREDIRS => 10,
           CURLOPT_TIMEOUT => 15,
           CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
           CURLOPT_CUSTOMREQUEST =>'POST',
           CURLOPT_POSTFIELDS =>json_encode($request['parameter']),
           CURLOPT_HTTPHEADER => [   
             "accept: application/json",
             "content-type: application/json"
           ],
         ]);
         $response = curl_exec($curl); 
        
         if(curl_errno($curl)){
            $response   =   self::response(json_encode(array("errorCode"=>"PayesMoney-001","error_code"=>curl_errno($curl),"message"=>curl_error($curl),"errorMessage"=>"Unable to get response please try again later"))); 
        }else if(isset($response) && $response != ""){
              $response   =   self::response($response);
        }else{
            $response   =   self::response(json_encode(array("errorCode"=>"PayesMoney-001","error_code"=>90,"message"=>"empty curl response","errorMessage"=>"Unable to get response please try again later"))); 
        }
       // dd($response);
        self::writelog("RESPONSE".$num,$response,$request['apiname']);
        return $response;
     }
 

   
    public static function docheckOp($request){ 
        $data = [
            'method' => $request['method'],
            'apiname'=> $request['apiname'],
            'url'    => static::$liveurl.'getop', 
            'parameter' => [ 
                    'mobileno'   => $request['canumber']           
            ]
        ];
       return self::hittingwithoutkey($data);
    }

    public static function hittingdocheckRoffer($request){ 
        $num    =   time();
         self::writelog("REQUEST".$num,$request,$request['apiname']); 
         $curl = curl_init();
         curl_setopt_array($curl, [
           CURLOPT_URL => 'https://api.bestapi.in/api/v1/RofferCheck',
           CURLOPT_RETURNTRANSFER => true,
           CURLOPT_ENCODING => "",
           CURLOPT_MAXREDIRS => 10,
           CURLOPT_TIMEOUT => 15,
           CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
           CURLOPT_CUSTOMREQUEST =>'POST',
           CURLOPT_POSTFIELDS =>json_encode($request['parameter']),
           CURLOPT_HTTPHEADER => [   
             "accept: application/json",
             "content-type: application/json"
           ],
         ]);
         $response = curl_exec($curl); 
        
         if(curl_errno($curl)){
            $response   =   self::response(json_encode(array("errorCode"=>"PayesMoney-001","error_code"=>curl_errno($curl),"message"=>curl_error($curl),"errorMessage"=>"Unable to get response please try again later"))); 
        }else if(isset($response) && $response != ""){
              $response   =   self::response($response);
        }else{
            $response   =   self::response(json_encode(array("errorCode"=>"PayesMoney-001","error_code"=>90,"message"=>"empty curl response","errorMessage"=>"Unable to get response please try again later"))); 
        }
       // dd($response);
        self::writelog("RESPONSE".$num,$response,$request['apiname']);
        return $response;
     }
    public static function docheckRoffer($request){ 
        $data = [
            'method' => $request['method'],
            'apiname'=> $request['apiname'],
            'url'    => static::$liveurl.'RofferCheck', 
            'parameter' => [ 
                    'mobileno'   => $request['canumber'],
                    'opcode'     => $request['operator']           
            ]
        ];
        
       return self::hittingdocheckRoffer($data);
    }

    
    
}