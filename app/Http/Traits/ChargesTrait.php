<?php

namespace App\Http\Traits;

use GuzzleHttp\Client;
use DateTime;
use Illuminate\Http\Request;
use App\Models\CommissionTemplate;
use App\Models\CommissionModel;
use Illuminate\Support\Facades\Hash; 
use Illuminate\Support\Facades\DB;

trait ChargesTrait
{

    public static function getrechargecomm($amount, $agent = "", $operator = '', $type = "4") {
        $rcomm = 0;
        $dcomm = 0; 
        $sdcomm = 0;
        $agent = self::get_charge_from_db_withoutresponse(array('userid' => $agent, "type" => $type));
        
        if (!empty($agent)) {
            $commission = self::get_charge_temp_db_withoutresponse(array('id' => $agent['tempid'], "type" => $type)); 
            if (!empty($commission)) {
                foreach ($commission as $key => $value) {  
                    if ($operator == $value['operatorid']) {
                        $rcomm = $amount * ($value['comm_retailer'] / 100);
                        $dcomm = $amount * ($value['comm_distributor'] / 100); 
                        $sdcomm = $amount * ($value['comm_superdistributor'] / 100); 
                    }
                }
            } else {
                if ($operator == 33 || $operator == 34) {
                    $rcomm = 0;
                    $dcomm = 0;
                    $pcomm = 0;
                    $sdcomm = 0;
                } elseif ($operator == 11) {
                    $rcomm = $amount * (1 / 100);
                    $dcomm = $amount * (0.2 / 100);
                    $pcomm = 0;
                    $sdcomm = $amount * (0.2 / 100);
                } else {
                    $rcomm = $amount * (1.5 / 100);
                    $dcomm = $amount * (0.2 / 100);
                    $pcomm = 0;
                    $sdcomm = $amount * (0.2 / 100);
                }
            }
        } else {
            $rcomm = $amount * (1.5 / 100);
            $dcomm = $amount * (0.2 / 100); 
            $sdcomm = $amount * (0.2 / 100);
        }
        return array("comm" => $rcomm, "dcomm" => $dcomm, "sdcomm" => $sdcomm );
           
         
    }

    public static function get_charge_temp_db_withoutresponse($req) {
        if (!empty($req)) {
            $row = CommissionTemplate::select("commission")->where($req)->first();  
             
            return json_decode($row['commission'], true);
        } else {
            return array("status" => false, "statuscode" => 2001, "message" => "No Commission found for this user please wait for the commission");
        }
    }
    private static function get_charge_from_db_withoutresponse($req) {
        if (!empty($req)) { 
            $row = CommissionModel::select("tempid")->where($req)->first();   
            return $row;
        } else {
            return array("status" => false, "statuscode" => 2001, "message" => "No Commission found for this user please wait for the commission");
        }
    }
}