<?php

namespace App\Http\Controllers\BussinessBanking;

use App\Http\Controllers\Controller;
use App\Http\Traits\CommonTrait;
use App\Models\BankList;
use App\Models\User;
use App\Models\BankDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Traits\HeaderTrait;
use Illuminate\Support\Facades\DB;

class PartnerController extends Controller
{
    use CommonTrait,HeaderTrait;
    public function __construct()
    {
        $this->status = ['0'=>'Deactive','1'=>'Active'];
    }
    public function partnerList(Request $request)
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
            
            $searchColumn = ['users.fullname','users.username','users.email','users.phone','users.status'];
            
            $select = ['users.id','users.fullname as partner_name','users.username as user_id','users.email','users.phone','users.status','users.created_at'];
            
            $query = DB::connection('pgsql')->table('users');
            $query->select($select);
            // $query->whereDate('users.created_at', '>=', $startdate);
            // $query->whereDate('users.created_at', '<=', $enddate);

            if ($status) {
                $query->where('users.status', $status);
            }

            $recordsTotal = $query->count();
            if(!empty($searchvalue)){
                $query->where(function($query) use ($searchColumn, $searchvalue){
                    foreach($searchColumn as $column){
                        $query->orWhere($column, 'like', '%' .  trim($searchvalue) . '%');
                    }
                });                
            }

            (!empty($orderby) && !empty($order))? $query->orderBy('users.'.$orderby, $order): $query->orderBy("users.id", "desc");
            $length = (!empty($request->length))? $request->length: 20;
            $start  = (!empty($request->start))? $request->start: 0;
            $data   = $query->skip($start)->take($length)->get();
            $recordsFiltered  = count($data);

            foreach($data as $key => $val){
                $linkedaccounts = BankDetails::select('bank_id','user_id')->where('user_id',$val->id)->groupBy('bank_id')->get();
                $banks = array();
                foreach($linkedaccounts as $linkedaccount){
                    $bankDetails = BankList::select('id','name','logo')->where('id',$linkedaccount->bank_id)->first();
                    $banks[] = $bankDetails->toArray();
                }
                $data[$key]->status = $this->status[$val->status];
                $data[$key]->active_banks = $banks;
                $data[$key]->created_at = date("d-m-Y H:i:s",strtotime($val->created_at));
            }


            $headerdata = $this->partners();
            if(!empty($data)){
                return $this->response('success', ['message' => "List fetched successfully!",'header' => $headerdata,'data'=>$data,'recordsFiltered' => $recordsFiltered,'recordsTotal'    => $recordsTotal]);
            }else{
                return $this->response('noresult', ['message' => "No record found.",'header' => $headerdata]);
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function updatePartner(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required',
                'status' => 'required',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $user = DB::connection('pgsql')->table('users')->where('id', $request->user_id)->update(array('status'=>$request->status));
            
            return $this->response('success', ['message'=>'Updated Succesfully']);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }
}