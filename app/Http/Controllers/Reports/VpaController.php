<?php

namespace App\Http\Controllers\Reports;

use App\Models\BankDetails;
use App\Models\MerchantVpa;
use App\Models\MerchantUpi;
use Illuminate\Http\Request;
use App\Http\Traits\CommonTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Traits\HeaderTrait;
use Illuminate\Support\Facades\DB;


class VpaController extends Controller
{
    use CommonTrait, HeaderTrait;
    
    public function __construct()
    {
         $this->statuscode = "success";
        $this->response   = [];
        $this->adm_status_array = ['1' => 'Success', '2' => 'In Process', '3' => 'Processing', '4' => 'Processed', '0' => 'Failed'];
        $this->status_array = ['1' => 'Success', '0' => 'Failed'];
        $this->qrtypes = ['1' => 'Static', '2' => 'Dynamic'];
        $this->status = ['0'=>'Deactive','1'=>'Active'];
    }

    public function vpa(Request $request){

        try {
            $startdate = $request->startdate;
            $enddate = $request->enddate;
            $searchvalue = $request->searchvalue;
            $orderby= $request->orderby;
            $order  = $request->order;
            $status        = $request->status;
            $start         = $request->start;
            $length        = $request->length;

            $startdate = !empty($startdate)?date('Y-m-d',strtotime($startdate)):date('Y-m-d');
            $enddate = !empty($enddate)?date('Y-m-d',strtotime($enddate)):date('Y-m-d');
            
            $searchColumn = ['vpa.id','vpa.vpa','vpa.merchantID','vpa.pan','vpa.mobile','vpa.status',
            'users.fullname as merchant','bank_lists.name as bank','vpa.created_at as created'];
            
            $select = ['vpa.id','vpa.vpa','vpa.merchantID','vpa.customer_name','vpa.pan','vpa.mobile','vpa.status',
            'users.fullname as partner','bank_lists.name as bank','vpa.created_at as created'];
            
            $query = DB::connection('pgsql')->table('merchant_vpas as vpa');
            $query->join('users', 'users.id', '=', 'vpa.userid');
            $query->join('bank_lists', 'bank_lists.id', '=', 'vpa.bank_id');
            $query->select($select);
            $query->whereDate('vpa.created_at', '>=', $startdate);
            $query->whereDate('vpa.created_at', '<=', $enddate);
            if ($request->has('user_id')) {
                $query->where('vpa.userid', $request->user_id);
            }
            if ($request->has('bank_id')) {
                $query->where('vpa.bank_id', $request->bank_id);
            }
            if ($status) {
                $query->where('vpa.status', $status);
            }
            $recordsTotal = $query->count();
            if(!empty($searchvalue)){
                $query->where(function($query) use ($searchColumn, $searchvalue){
                    foreach($searchColumn as $column){
                        $query->orWhere($column, 'like', '%' .  trim($searchvalue) . '%');
                    }
                });                
            }

            (!empty($orderby) && !empty($order))? $query->orderBy('vpa.'.$orderby, $order): $query->orderBy("vpa.id", "desc");
            $length = (!empty($request->length))? $request->length: 20;
            $start  = (!empty($request->start))? $request->start: 0;
            $data   = $query->skip($start)->take($length)->get();
            $recordsFiltered  = count($data);
            $headdata = $this->vpastatement();
            
            foreach($data as $key => $val){
                $data[$key]->status = $this->status[$val->status];
                $data[$key]->created_at = date("d-m-Y H:i:s",strtotime($val->created_at));
            }
                if(!empty($data)){
                    // $qrdata = array();
                    // foreach( $data as $upidata ){ 
                    //     $nid = $upidata['refId'];
                    //     $sumamount = MerchantUpi::where('qr_refid',$nid)->where('userid', Auth::user()->id)->get()->sum('amount'); 
                    //     $upidata['amount_sum'] = $sumamount;
                    //     $qrdata[] = $upidata;
                    // }
                    return $this->response('success', ['message' => "Success.",'header' => $headdata,'data' => $data,'recordsFiltered' => $recordsFiltered,'recordsTotal'    => $recordsTotal]);
                } 
                else {
                    return $this->response('noresult', ['message' => "No result Found.",'header' => $headdata,'data' => array(),'recordsFiltered' => 0,'recordsTotal'    => $recordsTotal]);
                }
               // return $this->response($status, $data);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        } 
    }

    public function singleVpa(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'merchantID' => 'required',
                'vpa' => 'required',
            ]);
    
            if ($validator->fails()) {
                $message   = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $vpadetails = MerchantVpa::select(
                'merchantID','userid','vpa','pan','mobile','acc_no','ifsccode','status','qr_name','image', DB::raw('DATE(created_at) AS created')
            )->where('merchantID', $request->merchantID)->where('vpa', $request->vpa)->first();
            
            if($vpadetails){
                $vpadata = $vpadetails->toArray();
                $user = DB::connection('pgsql')->table('users')->where('id',$vpadata['userid'])->first();
                $req = [
                    'token' => $user->onboard_token,
                ];
                // $resp = Curl::getbussinessname($req);
                $resp = [
                    'statuscode' => 200,
                    'status' => true,
                    'name' => "PaySprint Private Limited",
                    'message' => "Name fetched successfully"
                ];

                if(!empty($resp) && isset($resp['statuscode']) && $resp['statuscode'] == 200 && isset($resp['status']) && $resp['status'] == true){
                    $merchant_name = $resp['name'];
                }else{
                    $merchant_name = $user->name;
                }
                
                $vpadata['holder_name'] = BankDetails::where('account_number', $vpadata['acc_no'])->where('user_id', $user->id)->pluck('holderName')->first();
                $vpadata['payments'] = MerchantUpi::where('merchant_code', $request->merchantID)->where('status',1)->where('userid', $user->id)->count();
                $vpadata['amount_received'] = MerchantUpi::where('merchant_code', $request->merchantID)->where('status',1)->where('userid', $user->id)->sum('amount');
                $vpadata['merchant_name'] = $merchant_name;
                $vpadata['shop_name'] = $vpadata['qr_name'];
                return $this->response('success', ['statuscode'=>200,'message' => "Data fetched successfully!",'data'=>$vpadata]);
            }else{
                return $this->response('incorrectinfo', ['message' => "Incorrect Info!"]);
            }   
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        } 
    }

}