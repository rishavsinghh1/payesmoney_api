<?php
namespace App\Libraries\Common;

use Illuminate\Support\Facades\Schema; 
use App\Models\Commission;
use App\Models\User as usermodel;

class User
{
    public static $sid = 20231001;
    public static $aid = 20231002;
    
   
    public static function dateonlyFormat($date)
    {
        if (!empty($date) && !is_null($date)) {
            return date("d F, Y", strtotime($date));
        }
    }
}