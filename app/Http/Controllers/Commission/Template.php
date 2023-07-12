<?php

namespace App\Http\Controllers\Commission;

use App\Http\Controllers\Controller;
use App\Http\Traits\CommonTrait;
use App\Http\Traits\CommissionTrait;
use App\Models\CommissionTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Traits\HeaderTrait;

class Template extends Controller
{
    use CommonTrait,HeaderTrait,CommissionTrait;
    public function __construct() {
        $this->type = array(
            1 =>'BILL SS', 2 =>'MOBI QUICK WALLET', 3 =>'PAYTM WALLET', 4 =>'Recharge',5 =>'DMT',6 =>'Cash Deposite',7 =>'Aadharpay', 
            8 => 'AEPS CW', 9 =>  'Mini Statement', 10 => 'PG', 11 => 'Statement', 12 => 'MATM', 13 => 'LIC bill',14 => 'Payout',
            15 => 'PAN', 16 => 'VLE PAN', 17 => 'CC BILL PAY',18 => 'MI RECHARGE',20 => 'FASTAG'
        );
        $this->status 	= ['Inactive','Active'];
        $this->ctype 	= ['Percentage','Fixed'];
    }
    public function list(Request $request)
    {
        try {
            $validated = Validator::make($request->all(), [
                'type'   => 'required',
            ]);
            if ($validated->fails()) {
                $message   = $this->validationResponse($validated->errors());
                return $this->response('validatorerrors', $message);
            }
           
            $results = $this->signlequery('commission_template',["type"=>$request->type]);
            $data1 = [];
                if(!empty($results)){
                    foreach($results as $info){             
                        $sub_array = [];
                        $sub_array['id']       	=   $info->id;
                        $sub_array['name']      =   $info->name;
                        $sub_array['commission']=   $info->commission;
                        $sub_array['type']      =   $this->type[$info->type];
                        $sub_array['status']    =   $this->status[$info->status];  
                        $data1[] = $sub_array;
                    }
                } 
                if($data1){
                    return $this->response('success',['data' => $data1, 'message' => 'Template list  successfully!']);
                }else{
                    return $this->response('incorrectinfo');
                }
                 
             
        } catch (\Exception $e) {
            return  $this->response('internalservererror', ['message' => $e->getMessage()]);
        }
    } 
    public function getbyid(Request $request)
    {
        try {
            $validated = Validator::make($request->all(), [
                'tempid'   => 'required',
            ]);
            if ($validated->fails()) {
                $message   = $this->validationResponse($validated->errors());
                return $this->response('validatorerrors', $message);
            }
           
            $results = $this->signlequery('commission_template',['id'=>$request->tempid]);
            $data1 = [];
                if(!empty($results)){
                    foreach($results as $info){             
                        $sub_array = [];
                        $sub_array['id']       	=   $info->id;
                        $sub_array['name']      =   $info->name;
                        $sub_array['commission']=   $info->commission;
                        $sub_array['type']      =   $this->type[$info->type];
                        $sub_array['status']    =   $this->status[$info->status];  
                        $data1[] = $sub_array;
                    }
                } 
                if($data1){
                    return $this->response('success',['data' => $data1, 'message' => 'Template Details Fetched']);
                }else{
                    return $this->response('incorrectinfo');
                }
                 
             
        } catch (\Exception $e) {
            return  $this->response('internalservererror', ['message' => $e->getMessage()]);
        }
    } 
    public function create(Request $request)
    {
        try {
            $validated = Validator::make($request->all(), [
                'type'   => 'required',
                'commission'  => 'required',
                'name'  => 'required',
            ]);
            if ($validated->fails()) {
                $message   = $this->validationResponse($validated->errors());
                return $this->response('validatorerrors', $message);
            }
                $CommList = new CommissionTemplate();
                $CommList->type = $request->type;
                $CommList->commission = $request->commission;
                $CommList->name = $request->name;
                $CommList->status = 1; 
                $CommList->createby = 201551;
                $CommList->save();
            if($CommList){
                return $this->response('success',['message' => 'Template list  successfully!']);
            }else{
                return $this->response('incorrectinfo');
            }
                 
             
        } catch (\Exception $e) {
            return  $this->response('internalservererror', ['message' => $e->getMessage()]);
        }
    }
    
    public function update(Request $request)
    {
        try {
            $validated = Validator::make($request->all(), [
                'type'   => 'required',
                'commission'  => 'required',
                'name'  => 'required',
                'tempid'=> 'required',
            ]);
            if ($validated->fails()) {
                $message   = $this->validationResponse($validated->errors());
                return $this->response('validatorerrors', $message);
            } 
                $id = $request->tempid;
                $CommList = CommissionTemplate::find($id);
                if ($CommList) {
                $CommList = CommissionTemplate::where("id", $id)
                ->update(
                    ["type" => $request->type,
                    "commission" => $request->commission,
                    "name" => $request->name,
                    "status" => 1]
                ); 
                
                if($CommList){
                    return $this->response('success',['message' => 'Template list updated successfully!']);
                }else{
                    return $this->response('incorrectinfo');
                }
            }
                 
             
        } catch (\Exception $e) {
            return  $this->response('internalservererror', ['message' => $e->getMessage()]);
        }
    }

     
}