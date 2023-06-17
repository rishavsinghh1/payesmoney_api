<?php

namespace App\Http\Controllers\Reports;

use Illuminate\Http\Request;
use App\Http\Traits\CommonTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Http\Traits\HeaderTrait;
use App\Models\VirtualAccount;
use App\Models\VaTransactions;
use Illuminate\Support\Facades\DB;

class VaController extends Controller
{
    use CommonTrait, HeaderTrait;

    public function __construct()
    {
        $this->status = ['0'=>'Deactive','1'=>'Active'];
		$this->paymentmode  = array('N' => "NEFT", 'R' => "RTGS",'I' => "FT", 'O' => "IMPS" , 'U' => "UPI");
        $this->TransactionStatus = ['1' => 'Success','2' => 'Processing','3' => 'Rejected'];
    }
    
    public function list(Request $request)
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
            
            $searchColumn = ['va.acc_no','va.prefix','va.name','va.email','va.phone','va.pan','va.pincode',
            'va.type'];
            
            $select = ['va.id','va.acc_no','va.name','va.email','va.phone','va.pan',
            'va.status','users.fullname as partner','bank_lists.name as bank','va.created_at'];
            
            $query = DB::connection('pgsql')->table('va');
            $query->join('users', 'users.id', '=', 'va.userid');
            $query->join('bank_lists', 'bank_lists.id', '=', 'va.bank_id');
            $query->select($select);
            $query->whereDate('va.created_at', '>=', $startdate);
            $query->whereDate('va.created_at', '<=', $enddate);

            if ($request->has('user_id')) {
                $query->where('va.userid', $request->user_id);
            }
            if ($request->has('bank_id')) {
                $query->where('va.bank_id', $request->bank_id);
            }
            if ($status) {
                $query->where('va.status', $status);
            }

            $recordsTotal = $query->count();
            if(!empty($searchvalue)){
                $query->where(function($query) use ($searchColumn, $searchvalue){
                    foreach($searchColumn as $column){
                        $query->orWhere($column, 'like', '%' .  trim($searchvalue) . '%');
                    }
                });                
            }

            (!empty($orderby) && !empty($order))? $query->orderBy('va.'.$orderby, $order): $query->orderBy("va.id", "desc");
            $length = (!empty($request->length))? $request->length: 20;
            $start  = (!empty($request->start))? $request->start: 0;
            $data   = $query->skip($start)->take($length)->get();
            $recordsFiltered  = count($data);
            foreach($data as $key => $val){
                $data[$key]->status = $this->status[$val->status];
                $data[$key]->created_at = date("d-m-Y H:i:s",strtotime($val->created_at));
            }
            $headerdata = $this->vastatement();
            if(!empty($data)){
                return $this->response('success', ['message' => "List fetched successfully!",'header' => $headerdata,'data'=>$data,'recordsFiltered' => $recordsFiltered,'recordsTotal'    => $recordsTotal]);
            }else{
                return $this->response('noresult', ['message' => "No record found.",'header' => $headerdata]);
            }
            
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        } 
    }

    

    public function statement(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'va_no' => 'required',
            ]);
    
            if ($validator->fails()) {
                $message   = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $VirtualAccount = VirtualAccount::select('id','userid','bank_id','prefix','acc_no','name','email','phone','pan','pincode','min_limit','max_limit','type','status', DB::raw('DATE(created_at) AS created'))->where('acc_no', $request->va_no)->first();
            
            if($VirtualAccount){
                $VirtualAccountData = $VirtualAccount->toArray();
                $VirtualAccountData['paymentss'] = VaTransactions::where('va_no', $request->va_no)->where('userid', Auth::user()->id)->count();
                $VirtualAccountData['amount_received'] = VaTransactions::where('va_no', $request->va_no)->where('userid', Auth::user()->id)->sum('amount');
                return $this->response('success', ['statuscode'=>200,'message' => "Data fetched successfully!",'data'=>$VirtualAccountData]);
            }else{
                return $this->response('incorrectinfo', ['message' => "Incorrect Info!"]);
            }   
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        } 
    }

    public function transactions(Request $request)
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
            
            $searchColumn = ['vat.va_no','vat.amount','vat.p_mode','vat.remitter_name','vat.remitter_ac_no',
            'vat.txn_date','vat.utr','va.acc_no as acc_no','va.name as name'];
            
            $select = ['vat.created_at','vat.va_no','vat.amount','vat.p_mode','vat.remitter_name','vat.remitter_ac_no',
                'vat.txn_date','vat.utr','vat.status','va.acc_no as acc_no','va.name as name','users.fullname as merchant','bank_lists.name as bank'];
            
            $query = DB::connection('pgsql')->table('va_transactions as vat');
            $query->join('va', 'va.acc_no', '=', 'vat.va_no');
            $query->join('users', 'users.id', '=', 'va.userid');
            $query->join('bank_lists', 'bank_lists.id', '=', 'va.bank_id');
            $query->select($select);
            $query->whereDate('vat.created_at', '>=', $startdate);
            $query->whereDate('vat.created_at', '<=', $enddate);

            if ($request->has('user_id')) {
                $query->where('vat.userid', $request->user_id);
            }
            if ($request->has('bank_id')) {
                $query->where('vat.bank_id', $request->bank_id);
            }
            if ($status) {
                $query->where('vat.status', $status);
            }

            $recordsTotal = $query->count();
            if(!empty($searchvalue)){
                $query->where(function($query) use ($searchColumn, $searchvalue){
                    foreach($searchColumn as $column){
                        $query->orWhere($column, 'like', '%' .  trim($searchvalue) . '%');
                    }
                });                
            }

            (!empty($orderby) && !empty($order))? $query->orderBy('vat.'.$orderby, $order): $query->orderBy("vat.id", "desc");
            $length = (!empty($request->length))? $request->length: 20;
            $start  = (!empty($request->start))? $request->start: 0;
            $data   = $query->skip($start)->take($length)->get();
            $recordsFiltered  = count($data);
            $headdata = $this->vatransactionshead();
            $transactions =  array();
            if(!empty($data)){
                foreach($data as $singleTran){
                    $transactions[] = [
                        'created_at' => date("d-m-Y H:i:s",strtotime($singleTran->created_at)),
                        'account_name' => $singleTran->name,
                        'merchant' => $singleTran->merchant,
                        'bank' => $singleTran->bank,
                        'acc_no' => $singleTran->acc_no,
                        'remitter_name' => $singleTran->remitter_name,
                        'remitter_ac_no'=> $singleTran->remitter_ac_no,
                        'p_mode'=> $singleTran->p_mode,
                        'amount' => $singleTran->amount,
                        'utr' => $singleTran->utr,
                        'txn_date' => date("d-m-Y",strtotime($singleTran->txn_date)),
                        'status' => $this->TransactionStatus[$singleTran->status],
                    ];
                }
                return $this->response('success', ['message' => "Success.",'header' => $headdata,'data' => $transactions,'recordsFiltered' => $recordsFiltered,'recordsTotal'    => $recordsTotal]);
            }else{
                return $this->response('noresult', ['statuscode'=>200,'header' => $headdata ]);
            }   
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        } 
    }

}