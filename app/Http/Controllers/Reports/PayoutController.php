<?php
namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Beneficiry;
use App\Models\BankDetails;
use App\Models\BankList;
use App\Models\Payout;
use App\Http\Traits\CommonTrait;
use App\Http\Traits\HeaderTrait;
use App\Libraries\Common\Guzzle;

class PayoutController extends Controller
{
    use CommonTrait, HeaderTrait;
    public function __construct()
    {
        $this->status = ['0'=>'Deleted','1'=>'Active','2'=>'Pending'];
        $this->Stmtstatus = ['0'=>'Refund','1'=>'Success','2'=>'Process','3'=>'Pending'];
    }
    
     public function list(Request $request)
    {
        try {
            $search = $request->searchvalue;
            $orderby= $request->orderby;
            $order  = $request->order;
            $searchColumn = ["B.id","B.name","B.accno","B.mobile","B.status","users.fullname"];
            $select = ['users.fullname as partner','B.id','B.name','B.cpname','B.user_id','B.mobile','B.accno','B.status','B.email','B.mobile','B.bankname','B.ifsc','B.mode','B.remarks','B.created_at'];
            $query = DB::connection('pgsql')->table('beneficiries as B');
            $query->select($select);
            $query->join('users', 'users.id', '=', 'B.user_id');
            $totalCount = $query->count();
            if(!empty($search)){
                $query->where(function($query) use ($searchColumn, $search){
                    foreach($searchColumn as $column){
                        $query->orwhere($column, 'like', '%' .  trim($search) . '%');
                    }
                });                
            }
            (!empty($orderby) && !empty($order))? $query->orderBy("B.".$orderby, $order): $query->orderBy("B.id", "desc");
            $length = (!empty($request->length))? $request->length: 20;
            $start  = (!empty($request->start))? $request->start: 0;
            $list   = $query->skip($start)->take($length)->get();
            foreach($list as $key => $val){
                $list[$key]->tstatus = $val->status;
                $list[$key]->status = $this->status[$val->status];
                $list[$key]->createdat = date('d-m-Y',strtotime($val->created_at));
                unset($list[$key]->created_at);
            }
            $count  = count($list);
            $header = $this->beneficiarylist();
            $details = [
                "message" => "Beneficiary list.",
                "recordsFiltered" => $count,
                "recordsTotal" => $totalCount,
                "header" => $header,
                "data" => $list
            ];
            return $this->response('success', $details); 
        } catch (\Exception $e) {
            return  $this->response('internalservererror', ['message' => $e->getMessage()]);
        }
    }


    ///statement
    #Payout statement method.
    public function statement(Request $request) {
        try {
                        
            $startdate = $request->startdate;
            $enddate = $request->enddate;
            $search = $request->searchvalue;
            $orderby= $request->orderby;
            $order  = $request->order;

            $startdate = !empty($startdate)?date('Y-m-d',strtotime($startdate)):date('Y-m-d');
            $enddate = !empty($enddate)?date('Y-m-d',strtotime($enddate)):date('Y-m-d');
            $searchColumn = ["P.id","P.bene_acc_no","P.amount","P.mode","P.bank_urn","P.utr_rrn",'users.fullname'];
            $select = ["users.fullname as partner",'P.id','P.refid','P.userid','P.bene_acc_no','P.urn','P.amount','P.mode','P.status','P.remarks','P.bankname', 'P.bene_acc_ifsc', 'P.bank_urn','P.utr_rrn','P.addeddate','P.created_at'];
            $query = DB::connection('pgsql')->table('payouts as P');
            $query->select($select);
            $query->join('users', 'users.id', '=', 'P.userid');
            $query->whereDate('P.addeddate', '>=', $startdate);
            $query->whereDate('P.addeddate', '<=', $enddate);

            //$username = "RMY001823";
            $totalCount = $query->count();
            if(!empty($search)){
                $query->where(function($query) use ($searchColumn, $search){
                    foreach($searchColumn as $column){
                        $query->orWhere($column, 'like', '%' .  trim($search) . '%');
                    }
                });                
            }
            (!empty($orderby) && !empty($order))? $query->orderBy("P.".$orderby, $order): $query->orderBy("P.id", "desc");
            $length = (!empty($request->length))? $request->length: 20;
            $start  = (!empty($request->start))? $request->start: 0;
            $list   = $query->skip($start)->take($length)->get();

            foreach($list as $key => $val){
                $list[$key]->status = $this->Stmtstatus[$val->status];
                $list[$key]->createdat = date("d-m-Y H:i:s",strtotime($val->created_at));
                unset($val->created_at);
                unset($val->users);
            }
            $count  = count($list);
            $header = $this->payoutlist();
            $details = [
                "message" => "Payout list.",
                "recordsFiltered" => $count,
                "recordsTotal" => $totalCount,
                "header" => $header,
                "data" => $list
            ];
            return $this->response('success', $details); 
        } catch (\Exception $e) {
            return  $this->response('internalservererror', ['message' => $e->getMessage()]);
        }
       
        
    }
}
