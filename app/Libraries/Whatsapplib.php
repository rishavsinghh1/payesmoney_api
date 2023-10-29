<?php
namespace App\Libraries;
use App\Libraries\Common\Logs;   
use Carbon\Carbon;
class Whatsapplib{
    public static $url = "https://whatsbot.tech/api/";
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
           CURLOPT_TIMEOUT => 30,
           CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
           CURLOPT_CUSTOMREQUEST => $request['method'],
           CURLOPT_POSTFIELDS => [],
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
         self::writelog("RESPONSE".$num,$response,$request['apiname']);
         return $response;
    }


     public static function doSentMessage($request){ 
        $data = [
            'method' => 'POST',
            'apiname'=> 'Whatsapp',
            'url'    => static::$url.'send_sms'.'?api_token='.$request['api_token'].'&mobile='.$request['mobile'].'&message='.$request['message'], 
             
        ]; 
       return self::hitting($data);
    }

    public static function doSentimage($request){ 
        $data = [
            'method' => 'POST',
            'apiname'=> 'Whatsapp',
            'url'    => static::$url.'send_img'.'?api_token='.$request['api_token'].'&mobile='.$request['mobile'].'&img_url='.$request['image'].'&img_caption='.$request['caption'], 
             
        ];  
       return self::hitting($data);
    } 
}