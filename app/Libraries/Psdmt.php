<?php
namespace App\Libraries;
use App\Libraries\Common\Logs;   
use Carbon\Carbon;
class Psdmt{
    public static $url = "https://uat.bestapi.in/web-api/api/v1/";
    public static $liveurl = "https://api.bestapi.in/api/v1/";
   
    public static $authkey = "N2U1MmRiZDk3ZjA0MzBiNzBiM2UxZThmZGI3YjRkZTc="; 
    public static $authkeylocal = "ZDI1M2Q5NGIwMDBmNTdiZDJiODNjM2FlNGE1NTc0NWE="; 
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
         $curl = curl_init();
         curl_setopt_array($curl, [
           CURLOPT_URL => $request['url'],
           CURLOPT_RETURNTRANSFER => true,
           CURLOPT_ENCODING => "",
           CURLOPT_MAXREDIRS => 10,
           CURLOPT_TIMEOUT => 30,
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
         return $response;
    }

    public static function doqueryremitter($request){ 
        $data = [
            'method' => 'POST', 
            'apiname'=> 'queryremitter',
            'url'    => static::$liveurl.'dmt/get-remitter', 
            'parameter' => [ 
                    'mobile'     => $request['mobile'], 
                    'reqId'      =>  self::randString(14)        
            ]
        ]; 
       return self::hitting($data);
    }

    public static function registerremitter($request){ 
        $data = [
            'method' => 'POST', 
            'apiname'=> 'registerremitter',
            'url'    => static::$liveurl.'dmt/register-remitter', 
            'parameter' => [ 
                    'mobile'    => $request['mobile'],
                    'firstname' =>  strtoupper($request['firstname']),
                    'lastname'  =>  strtoupper($request['lastname']), 
                    'otp'       =>  $request['otp'],
                    'pincode'   =>  $request['pincode'],
                    'stateresp' =>  $request['stateresp'],
                    'bank3_flag'=>  'no',
                    'address'   =>  'Noida',
                    'dob'       =>  $request['dob'],  
                    'reqId'     =>  self::randString(14)        
            ]
           
        ]; 
       return self::hitting($data);
    }

    public static function fetchbeneficiary($request){ 
        $data = [
            'method' => 'POST', 
            'apiname'=> 'fetchbeneficiary',
            'url'    => static::$liveurl.'dmt/fetchbeneficiary', 
            'parameter' => [ 
                    'mobile'    => $request['mobile'], 
                    'reqId'     =>  self::randString(14)        
            ]
           
        ]; 
       return self::hitting($data);
    }
    public static function registerbeneficiary($request){ 
        $data = [
            'method' => 'POST', 
            'apiname'=> 'registerbeneficiary',
            'url'    => static::$liveurl.'dmt/registerbeneficiary', 
            'parameter' => [ 
                    'mobile'        => $request['mobile'], 
                    'benename'      => $request['benename'], 
                    'bankid'        => $request['bankid'], 
                    'accno'         => $request['accno'], 
                    'ifsccode'      => $request['ifsccode'], 
                    'verified'      => $request['verified'], 
                    'gst_state'     => $request['gst_state'], 
                    'dob'           => $request['dob'], 
                    'address'       => $request['address'], 
                    'pincode'       => $request['pincode'], 
                    'reqId'         =>  self::randString(14)        
            ]
           
        ]; 
       
       return self::hitting($data);
    } 
    public static function randString($length, $charset='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789')
    {
        $str = '';
        $count = strlen($charset);
        while ($length--) {
            $str .= $charset[mt_rand(0, $count-1)];
        }
        return $str;
    }
    

}