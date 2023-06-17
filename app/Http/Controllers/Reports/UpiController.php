<?php

namespace App\Http\Controllers\Reports;

use Illuminate\Http\Request;
use App\Http\Traits\CommonTrait;
use App\Http\Traits\HeaderTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class UpiController extends Controller
{
    use CommonTrait,HeaderTrait;
    public function __construct()
    {
         $this->statuscode = "success";
        $this->response   = [];
        $this->adm_status_array = ['1' => 'Success', '2' => 'In Process', '3' => 'Processing', '4' => 'Processed', '0' => 'Failed'];
        $this->status_array = ['1' => 'Success', '0' => 'Failed'];
        $this->qrtypes = ['1' => 'Static', '2' => 'Dynamic'];
        $this->TransactionStatus = ['1' => 'Success','2' => 'Initited','3' => 'Qr Generated','4' => 'Qr Expired','5' => 'Failed'];
    }
    public function report(Request $request)
    {
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
            
            $searchColumn = ['U.merchant_code','U.payer_name','U.payer_va','U.txn_completion_date','U.addeddate','U.txnid','U.original_bank_rrn','U.qr_type','U.bank_refid','vpa.acc_no as acc_no','vpa.vpa as vpa','vpa.ifsccode as ifsccode',
            'users.fullname as merchant'];
            
            $select = ['U.created_at','U.merchant_code','U.payer_name','U.payer_va','U.amount','U.txn_completion_date',
            'U.addeddate','U.status','U.txnid','U.original_bank_rrn','U.qr_type','U.bank_refid','U.charges',
            'U.gst','U.payer_amount','vpa.acc_no as acc_no','vpa.vpa as vpa','vpa.ifsccode as ifsccode',
            'users.fullname as merchant','bank_lists.name as bank'];
            
            $query = DB::connection('pgsql')->table('merchant_upis as U');
            $query->join('merchant_vpas as vpa', 'vpa.merchantID', '=', 'U.merchant_code');
            $query->join('users', 'users.id', '=', 'U.userid');
            $query->join('bank_lists', 'bank_lists.id', '=', 'U.bank_id');
            $query->select($select);
            $query->whereDate('U.created_at', '>=', $startdate);
            $query->whereDate('U.created_at', '<=', $enddate);

            if ($request->has('user_id')) {
                $query->where('U.userid', $request->user_id);
            }
            if ($request->has('bank_id')) {
                $query->where('U.bank_id', $request->bank_id);
            }
            if ($request->has('merchantID')) {
                $query->where('U.merchant_code', $request->merchantID);
            }
            if ($status) {
                $query->where('U.status', $status);
            }else{
                $query->whereIn('U.status', array(1,5));
            }
            
            $recordsTotal = $query->count();
            if(!empty($searchvalue)){
                $query->where(function($query) use ($searchColumn, $searchvalue){
                    foreach($searchColumn as $column){
                        $query->orWhere($column, 'like', '%' .  trim($searchvalue) . '%');
                    }
                });                
            }

            (!empty($orderby) && !empty($order))? $query->orderBy('U.'.$orderby, $order): $query->orderBy("U.id", "desc");
            $length = (!empty($request->length))? $request->length: 20;
            $start  = (!empty($request->start))? $request->start: 0;
            $data   = $query->skip($start)->take($length)->get();
            $recordsFiltered  = count($data);
            
            $headdata = $this->transactionshead();
            // print_r(Auth::user()->id);exit;
            $transactions = array();
            if(!empty($data)){
                foreach($data as $singleTran){
                    $transactions[] = [
                        'created_at' => date("d-m-Y H:i:s",strtotime($singleTran->created_at)),
                        'txnid' => $singleTran->txnid, 
                        'merchant' => $singleTran->merchant, 
                        'bank' => $singleTran->bank, 
                        'refid' => $singleTran->bank_refid, 
                        'charges' => $singleTran->charges, 
                        'gst' => $singleTran->gst, 
                        'addeddate' => date("d-m-Y",strtotime($singleTran->addeddate)), 
                        'qr_type' => $this->qrtypes[$singleTran->qr_type], 
                        'vpa' => $singleTran->vpa,
                        'payer_name' => $singleTran->payer_name,
                        'payer_va' => $singleTran->payer_va,
                        'payer_amount' => $singleTran->payer_amount,
                        'amount' => $singleTran->amount,
                        'rrn' => $singleTran->original_bank_rrn,
                        'completion_date' => date("d-m-Y",strtotime($singleTran->txn_completion_date)),
                        'status' => $this->TransactionStatus[$singleTran->status],
                    ];
                }
                return $this->response('success', ['message' => "Success.",'header' => $headdata,'data' => $transactions,'recordsFiltered' => $recordsFiltered,'recordsTotal'    => $recordsTotal]);
            }else{
                return $this->response('noresult', ['statuscode'=>200,'header' => $headdata]);
            }   
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }
}
