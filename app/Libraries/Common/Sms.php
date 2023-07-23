<?php

namespace App\Libraries\Common;
use Illuminate\Http\Request;
use App\Libraries\Common\Logs;
use App\Models\Sms_Model;
class Sms
{
    
    public static function sendsms($request, $result_config = "")
    {
        $senderid   =   "BESAPI";
        $filename   =   "PSPRNT.".$request['template'];
        
          if($filename != ""){
              $template =    trans($filename);
              $number   =$request['phone'];
             $message    =   $template['msg'];
             $tmpid      =   $template['id'];
            $user_id =  "25648542";
             $token =  "74487321763184fe1a662c9.63806065";
             $route =  "TR";
             $finalmsg = str_replace(array_keys($request['message']), array_values($request['message']), $message);
        
         $genurl = 'https://m1.sarv.com/api/v2.0/sms_campaign.php'.'?token='.$token.'&user_id='.$user_id.'&route='.$route.'&template_id='.$tmpid.'&sender_id='.$senderid.'&language=EN&template='.$finalmsg.'&contact_numbers='.$number;


             


            $response   =   self::hit($genurl); 
             
          }
        return  $response;
    }

    public static function sendMSG91sms($request)
    {

        $info   = Sms_Model::select('*')    
            ->where('status',1)
            ->where('message_type',$request['template']) 
            ->first();

            $new['flow_id']     =      $info->template_id;
            $new['sender']      =      $info->sender_id;
            $new['recipients']  = [
                                  $request['message']
                                  ];
            
             $response   =   self::cbis($new); 
             
             
        return  $response;
    }



    public static function cbis($msg) {
        Logs::writelogs(array("dir"=>"MSG91-sms","type"=>"Request","data"=>json_encode($msg)));
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.msg91.com/api/v5/flow/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($msg),
            CURLOPT_HTTPHEADER => array(
                'authkey: 385329AMXWpx08Qx6378810cP1',
                'Content-Type: application/json',
                'Cookie: PHPSESSID=iot6fm4a58e59mflevgr8jrug3'
            ),
        ));
       $response = curl_exec($curl);
        
        curl_close($curl);
        Logs::writelogs(array("dir"=>"cbis-sms","type"=>"Response","data"=>$response));
        return $response;
    } 

    public static function hit($genurl)
    {
         
        Logs::writelogs(array("dir"=>"sarv-sms","type"=>"Request","data"=>json_encode($genurl)));

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => $genurl,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        Logs::writelogs(array("dir"=>"sarv-sms","type"=>"Response","data"=>$response));
        return $response;
    }



}
?>