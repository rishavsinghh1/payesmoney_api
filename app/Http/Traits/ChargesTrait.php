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
                    $sdcomm = 0;
                } elseif ($operator == 11) {
                    $rcomm = $amount * (2.0 / 100);
            $dcomm = $amount * (0.5 / 100); 
            $sdcomm = $amount * (0.10 / 100);
                } else {
                    $rcomm = $amount * (2.0 / 100);
                    $dcomm = $amount * (0.5 / 100); 
                    $sdcomm = $amount * (0.10 / 100);
                }
            }
        } else {
            $rcomm = $amount * (2.0 / 100);
            $dcomm = $amount * (0.5 / 100); 
            $sdcomm = $amount * (0.10 / 100);
        }
        return array("comm" => $rcomm, "dcomm" => $dcomm, "sdcomm" => $sdcomm );
           
         
    }

    public static function calculatedmtslot($amount="",$agent="",$type="5"){
        $txn_amount = 5000;
        $total  =   0;
        $rcomm  =   0; 
        $dcomm  =   0;  
        $sdcomm =   0; 
        $i  =   0;
        $return_array = [];
        $agent  =   self::get_charge_from_db_withoutresponse(array('userid'=>$agent,"type"=>$type));
        $temp_id = $agent['tempid']!=''?$agent['tempid']:"";
       
        $commission = self::get_charge_temp_db_withoutresponse(array('id'=>$temp_id,"type"=>$type)); 
    
        $jsn =  json_decode('[ {"slab_min": 100,"slab_max": 1000,"commission_superdistributor": 0,"commission_distributor": 0,"commission_partner": 0,"commission_retailer": 4,"is_fixed": "1"},{"slab_min": 1001,"slab_max": 2000,"commission_superdistributor": 1, "commission_distributor": 1,"commission_partner": 0,"commission_retailer": 7,"is_fixed": "1"},{"slab_min": 2001,"slab_max": 3000,"commission_superdistributor": 1,"commission_distributor": 1,"commission_partner": 1,"commission_retailer": 10,"is_fixed": "1"},{"slab_min": 3001,"slab_max": 4000,"commission_superdistributor": 1, "commission_distributor": 2,"commission_partner": 0,"commission_retailer": 14,"is_fixed": "1"},{"slab_min": 4001,"slab_max": 5000,"commission_superdistributor": 1,"commission_distributor": 2,"commission_partner": 0,"commission_retailer": 17,"is_fixed": "1"}]',true);
        $commission =  !empty($commission)?$commission:$jsn;
      
        $div    =   $amount/$txn_amount;
        $mod    =   floor($div); 
        if($amount > $txn_amount){
            for($j=1;$j<=$mod;$j++){
                foreach ($commission as $key => $value) {
                    if($txn_amount  >= $value['slab_min'] && $txn_amount <= $value['slab_max']){
                        if($value['is_fixed'] == 0){    // 0-percentage, 1-fixed
                            $rcomm  = $txn_amount*($value['commission_retailer']/100);
                            $dcomm  = $txn_amount*($value['commission_distributor']/100); 
                            $sdcomm = $txn_amount*($value['commission_superdistributor']/100);
                        }
                        else{
                            $rcomm  =  $value['commission_retailer'];
                            $dcomm  =  $value['commission_distributor']; 
                            $sdcomm =  $value['commission_superdistributor'];
                        }
                    }
                }
                $return_array[$i] = array("amount"=>$txn_amount,"agent_charges"=>$rcomm,"dt_charges"=>$dcomm,"sd_charges"=>$sdcomm);
                $total  =   $total+$txn_amount;
                $i++;
            }
            if($total<$amount){
                $left_amount    =   $amount-$total;
                foreach ($commission as $key => $value) {
                    if($left_amount  >= $value['slab_min'] && $left_amount <= $value['slab_max']){
                        if($value['is_fixed'] == 0){    // 0-percentage, 1-fixed
                            $rcomm  = $left_amount*($value['commission_retailer']/100);
                            $dcomm  = $left_amount*($value['commission_distributor']/100); 
                            $sdcomm = $left_amount*($value['commission_superdistributor']/100);
                        }
                        else{
                            $rcomm  =  $value['commission_retailer'];
                            $dcomm  =  $value['commission_distributor']; 
                            $sdcomm =  $value['commission_superdistributor'];
                        }
                    }
                }
                $return_array[$i]   =   array("amount"=>$left_amount,"agent_charges"=>$rcomm,"dt_charges"=>$dcomm,"sd_charges"=>$sdcomm);
                $i++;
            }
        }else{
            foreach ($commission as $key => $value) {
                if($amount  >= $value['slab_min'] && $amount <= $value['slab_max']){
                    if($value['is_fixed'] == 0){    // 0-percentage, 1-fixed
                        $rcomm  = $amount*($value['commission_retailer']/100);
                        $dcomm  = $amount*($value['commission_distributor']/100); 
                        $sdcomm = $amount*($value['commission_superdistributor']/100);
                        $amount = $amount;
                    }
                    else{
                        $rcomm  =  $value['commission_retailer'];
                        $dcomm  =  $value['commission_distributor']; 
                        $sdcomm =  $value['commission_superdistributor'];
                        $amount = $amount;
                    }
                }
            }
            $return_array[$i] = array("amount"=>$amount,"agent_charges"=>$rcomm,"dt_charges"=>$dcomm,"sd_charges"=>$sdcomm);
        }
        return $return_array;
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