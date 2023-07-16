<?php
namespace App\Libraries\Common;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Logs
{
    public static function writelogs($data = "")
    {
        //return true;
        
        $path  = "logs/".date("Y-m-d")."/".$data['dir'];
        $file_name = $path."/logs.txt";
        if(!is_dir($path)){
            mkdir($path, 0777, TRUE);
        }
        $handle     =   fopen($file_name, 'a');
        fwrite($handle,date("Y-m-d H:i:s")." ".$data['type'].": ".$data['data']." \n");
        fclose($handle); 
    } 
}
