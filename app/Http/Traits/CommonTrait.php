<?php

namespace App\Http\Traits;

use GuzzleHttp\Client;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Config;
use Illuminate\Support\Facades\DB;
use App\Models\UserPasswordDetails as UserPassword;

trait CommonTrait
{
    public static function response($input = '', $params = array())
    {
        $statusResp = array(
            'success' => array(
                'statuscode' => 200,
                'status' => true,
                'message' => 'Success!',
            ),
            'noresult' => array(
                'statuscode' => 200,
                'status' => false,
                'message' => 'No Record Found!',
            ),
            'exception' => array(
                'statuscode' => 201,
                'status' => false,
                'message' => 'Exception Error!',
            ),
            'incorrectinfo' => array(
                'statuscode' => 201,
                'status' => false,
                'message' => 'The provided information is incorrect!',
            ),
            'updateError' => array(
                'statuscode' => 201,
                'status' => false,
                'message' => 'Error while Updating!',
            ),
            'notvalid' => array(
                'statuscode' => 201,
                'status' => false,
                'message' => 'The provided information is not Valid!',
            ),
            'apierror' => array(
                'statuscode' => 201,
                'status' => false,
                'message' => 'API is not responding right now!',
            ),
            'validatorerrors' => array(
                'statuscode' => 422,
                'status' => false,
                'message' => 'Validation Error!',
            ),
            'oops' => array(
                'statuscode' => 404,
                'status' => false,
                'message' => 'something went wrong please try after some time!',
            ),
            'internalservererror' => array(
                'statuscode' => 500,
                'status' => false,
                'message' => 'HTTP INTERNAL SERVER ERROR!',
            ),
        );

        if (isset($statusResp[$input])) {
            $data = $statusResp[$input];
            $code = isset($params['code']) ? $params['code'] : $statusResp[$input]['statuscode'];
            if (!empty($params)) {
                $data = array_merge($data, $params);
            }
            return response()->json($data, $code);
        } else {
            return response()->json($params);
        }
    }

    public static function validationResponse($message)
    {
        $err = $message->toArray();
        $msg = $errors = [];
        foreach ($err as $key => $value) {
            $msg[] = $value[0];
            $errors[$key] = $value[0];
        }
        $message = ['message' => implode('<br/>', $errors), 'msg' => $msg];
        return $message;
    }

    public static function is_expired($date, $minute)
    {
        $date1 = new DateTime($date);
        $now = new DateTime();
        $difference_in_seconds = $now->format('U') - $date1->format('U');
        $counterTime = 60 * $minute;

        if ($difference_in_seconds > $counterTime) {
            return true;
        } else {
            return false;
        }
    }

    public static function is_locked($date, $minute)
    {
        $date1 = new DateTime($date);
        $now = new DateTime();
        $difference_in_seconds = $now->format('U') - $date1->format('U');
        $counterTime = 60 * $minute;

        if ($difference_in_seconds < $counterTime) {
            return true;
        } else {
            return false;
        }
    }


    function convertString($string, $flag)
    {
        // for email addresses: do not obfuscate beyond at symbol
        $clear = strpos($string, "@");
        if ($flag == 0) {
            if ($clear === false)
                $clear = max(0, strlen($string) - 0);
            $hide = max(0, min($clear - 1, 0));
            $result = substr($string, 0, $hide) .
                str_repeat("x", $clear - $hide) .
                substr($string, $clear);
        } else if ($flag == 1) {
            if ($clear === false)
                $clear = max(0, strlen($string) - 0);
            $hide = max(0, min($clear - 1, 1));
            $result = substr($string, 0, $hide) .
                str_repeat("x", $clear - $hide) .
                substr($string, $clear);
        }
        return $result;
    }

    static function random_no($length, $charTyp = "")
    {
        $token = "";
        if ($charTyp == "") {
            $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $codeAlphabet .= "abcdefghijklmnopqrstuvwxyz";
            $codeAlphabet .= "0123456789";
        } else if ($charTyp == "number") {
            $codeAlphabet = "0123456789";
        }
        $max = strlen($codeAlphabet); // edited

        for ($i = 0; $i < $length; $i++) {
            $token .= $codeAlphabet[CommonTrait::crypto_rand_secure(0, $max - 1)];
        }
        return $token;
    }

    static function crypto_rand_secure($min, $max)
    {
        $range = $max - $min;
        if ($range < 1)
            return $min; // not so random...
        $log = ceil(log($range, 2));
        $bytes = (int) ($log / 8) + 1; // length in bytes
        $bits = (int) $log + 1; // length in bits
        $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter; // discard irrelevant bits
        } while ($rnd > $range);
        return $min + $rnd;
    }

    static function getinputtypes()
    {
        $result = ['1' => 'file + button', '2' => 'file', '3' => 'input'];
        return $result;
    }

    static function passwordError($input = "")
    {
        $passResp = array(
            'match' => array(
                'message' => "Password matched",
                'statuscode' => 200
            ),
            'wrong' => array(
                'message' => "Password not match",
                'statuscode' => 201
            ),
            'expire' => array(
                'message' => "Password expired",
                'statuscode' => 201
            ),
            'otpsent' => array(
                'allwoTwostep' => true,
                'message' => "Otp send successfully.",
                'statuscode' => 201
            ),
            'locked' => array(
                'message' => "Account locked after 3 incorrect attempts. Try after some time",
                'statuscode' => 201
            ),
            'blocked' => array(
                'message' => "Account blocked 5 incorrect attempts. Please Contact to admin or reset your password",
                'statuscode' => 201
            )
        );

        if (isset($passResp[$input])) {
            $data = $passResp[$input];
            return $data;
        } else {
            $data = $passResp['wrong'];
            return $data;
        }
    }

    static function otpsent()
    {
        $response = [
            'allwoTwostep' => true,
            'message' => "Otp send successfully.",
            'statuscode' => 201
        ];
        return $response;
    }
    public function checkPasswordMatch($reqPass, $isPswdMatch)
    {
        if (Hash::check($reqPass, $isPswdMatch->password)) {
            $pswdExpDate = $isPswdMatch->expired_at;
            $today_date = date('Y-m-d');
            if ($isPswdMatch->status != 1 || $today_date > $pswdExpDate) {
                UserPassword::where('id', $isPswdMatch->id)->update(["status" => 0]);
                $res = $this->passwordError('expire');
                return $res;
            } else {
                UserPassword::where('id', $isPswdMatch->id)->update(["login_attempt" => 0]);
                $res = $this->passwordError('match');
                return $res;
            }
        }else {
            $attempt =  $isPswdMatch->login_attempt+1;
            if($attempt > 4){
                $status = 0;
            }else{
                $status = 1;
            }
            UserPassword::where('id', $isPswdMatch->id)->update(["login_attempt" => $attempt,'status'=>$status]);
            $res = $this->passwordError('wrong');
            return $res;
        }
    }

    public function randomPassword() {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }
    static function upload_document(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'document' => 'required|' . $request->validation,
            'path' => 'required'
        ]);
        if ($validated->fails()) {
            $error = CommonTrait::validationResponse($validated->errors());
            return ['status' => false, 'error' => $error];
        }
        $filePath = $request->path . '/';
        $fileName = 'rlm' . time() . '.' . $request->document->extension();
        $request->document->move(rootDir() . $filePath, $fileName);
        $url = rootDir() . $filePath . $fileName;
        return ['status' => true, 'url' => $url];
    }

    static function nostro_type()
    {
        return
            [
                ['id' => 1, 'nostro' => 'BEN'],
                ['id' => 2, 'nostro' => 'OUR']
            ];
    }

    public function getorderstage($stage)
    {
        $orderStages = array('1' => 'New', '2' => 'Pending', '3' => 'Verified', '4' => 'Confirmed', '5' => 'Completed');
        return $orderStages[$stage];
    }


    public function encryption_private_data($data)
    {
        $key = openssl_random_pseudo_bytes(32);
        $encrypteddata = openssl_encrypt(json_encode($data), 'aes-256-cbc', $key, OPENSSL_RAW_DATA);
        openssl_private_encrypt($key, $encryptedKey, self::privatekey());
        $encodedData = base64_encode($encrypteddata);
        $encodedKey = base64_encode($encryptedKey);

        return ['token' => $encodedData, 'key' => $encodedKey];
    }

    public function encryption_public_data($data)
    {
        $key = openssl_random_pseudo_bytes(32);
        openssl_public_decrypt($encryptedKey, $key, self::publickey());
        $decryptedData = openssl_decrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA);

        return $decryptedData;
    }


    protected function privatekey()
    {
        return '-----BEGIN RSA PRIVATE KEY-----
        MIICXAIBAAKBgQDJJ3FNyqOa1XNblsByNQIDPMCSuTMBiAD78K34Rfi8FWF1P9zv
        a3bnrucr53eJzSSjfgEJ+1qtVQ5v2eSPwrfXI4x9QvpSqMutfHTSq3JudZMWSgcH
        kaAOJNG3jlqzkeUZQA07nBVKHQ8ZqaKqgnlM7pjbvKwgzUzinQ/0NZmARQIDAQAB
        AoGBALS9cfsZ5qMKw5o5/DUiF+rcvZOYQJJRp8C4Yzi/dl1ZQLZfaZ7einpmF2TF
        mA0DfLZCU6CqbrFryYsK12ms5g0bGX0mZr5fD238Uwe1dJZySdAalrmedZDaOKe3
        AZpWwxSVld2BsY8+BomI1O2AQnej6AvKEufyLSgj2U9Qv4qBAkEA5bjiJXqt/uYv
        FECMK9AtdOIbbeoXtIBxn2qxASPTSGT9RUIQAByRh5t3xlWtSj3bpQlexprMmyhk
        CdO7yWfd1QJBAOAp+og1vjfq6i4DsFrwy055PG8aZ+hBnqBMKLb+5LPUzJW5RZLh
        gri1vZeX6RYLZUuu8nY1SBr+BwSKBB+UoLECQBD6tVxn0OyCPwCUNMgYPwPgon5h
        SxdAVyWdUS/wYfF75Wx1EZGwiuEnEJdMRd6y68UrCCJN1smxFpPTXpHoZ3ECQFsf
        lXFjb3TpsNKNu1Xshqjazb9YW57ldecxrmddTHjx60x96RNhSrNtZanHHgBRF5dh
        gbydwjb+xrmIpU51K7ECQEPrUUVcjywtJcY8GoN1NwAm25By06XDwPY1MWqQnkfJ
        WPqmgzl5D09HNlKWmj4sqquFvusFCtt1tYEeAZC5R+0=
        -----END RSA PRIVATE KEY-----';
    }

    protected function publickey()
    {
        return '-----BEGIN PUBLIC KEY-----
        MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDJJ3FNyqOa1XNblsByNQIDPMCS
        uTMBiAD78K34Rfi8FWF1P9zva3bnrucr53eJzSSjfgEJ+1qtVQ5v2eSPwrfXI4x9
        QvpSqMutfHTSq3JudZMWSgcHkaAOJNG3jlqzkeUZQA07nBVKHQ8ZqaKqgnlM7pjb
        vKwgzUzinQ/0NZmARQIDAQAB
        -----END PUBLIC KEY-----';
    }

    function validatepan($data,$ignore=''){ 
            $query = DB::table('users');
            $query->select("id,pannumber");
            $query->where("pannumber",$data['pannumber']);     
        if(isset($data["id"]) && $data["id"]!=""){ 
            $query->where("id !=",$data['id']);
        }
        if(!empty($ignore)){
            $query->where_not_in('id',$ignore); 
        } 
        $total = $query->count(); 
        if($total == 0){
            return true;
        }else{
            return false;
        }
    }

    function uniquepan($pannumber,$userid=""){
        if(!empty($pannumber)){
            $query = DB::table('users');
            $query->where('pannumber',$pannumber);  
            if($userid!=""){
                $query->where("id !=",$userid); 
                $query->where("usertype !=",7);  
            } 
            $total = $query->count(); 
            return  array("status"=>true,"count"=>$total,"message"=>"Pan Number count");
        }else{
            return array("status"=>false,"message"=>"Pan Number is not avaliable");
        }
    }
    function uniquesubmobile($phone,$userid="",$ignore=""){
        if(!empty($phone)){
            $query = DB::table('users');
            $query->select("phone");
            $query->where("phone",$phone);  
            if($userid!=""){
                $query->where("id !=",$userid);   
            }
            if(!empty($ignore)){
                $query->where_not_in("id",$ignore);    
            }
            $total = $query->count(); 
            return  array("status"=>true,"count"=>$total,"message"=>"Phone Number count");
        }else{
            return array("status"=>false,"message"=>"Phone number is not avaliable");
        }
    }
    function getaSd($where=""){
        $query = DB::table('users');
        $query->select("*");
        $query->where($where);    
        return $query->get()->toArray()[0]; 
    } 
    function validateusername($username,$userid=""){ 
        $query = DB::table('users');
        $query->select("*");   
        $where  = array("username"=>$username);
        if($userid!=""){
            $where['id !='] =    $userid;
        }
        $query->where($where);   
        $total = $query->count(); 
        if($total==0){
            return true;
        }else{
            return false;
        }
    }

    function getadmin($id) {
        $where['users.id'] = $id;
        $where["users.status"] = 1;
        $where["users.role"] = 2;
        $where["permission.funding"] = 1; 
        $query = DB::table('users');
        $query->leftjoin('permission', 'permission.userid', '=', 'users.id'); 
        $query->select("users.id","users.fullname","users.username","users.cd_balance","users.credit");  
        $query->where($where);
        $query = $query->get()->toArray();
        return $query[0];
    }
    function getSuperadmin($id) {
        $where['users.id'] = $id;
        $where["users.status"] = 1;
        $where["users.role"] = 1;
        $where["permission.funding"] = 1; 
        $query = DB::table('users');
        $query->leftjoin('permission', 'permission.userid', '=', 'users.id'); 
        $query->select("users.id","users.fullname","users.username","users.cd_balance","users.credit");  
        $query->where($where);
        $query = $query->get()->toArray();
        return $query[0];
    }
    function config($key){ 
        $query = DB::table('config');
        $query->select("value"); 
        if(is_numeric($key)){
            $query->where("id",$key); 
        }else{
            $query->where("key",$key);  
        }
        $totaldata = $query->get()->toArray();
        
        return $totaldata[0]->value;
    }

    function generateRandomString($length) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }

}