<?php
namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request; 
use App\Http\Traits\CommonTrait;
use App\Http\Traits\HeaderTrait;
use App\Libraries\Common\Guzzle;
use DateTime;
use DateTimeZone;
use Carbon\Carbon;
class PayoutController extends Controller
{
    use CommonTrait, HeaderTrait;
    public function __construct()
    {
        $this->today      = Carbon::now()->toDateString();
        $this->status = ['0'=>'Deleted','1'=>'Active','2'=>'Pending'];  
        $this->Stmtstatus = ['0'=>'Refund','1'=>'Success','2'=>'Process','3'=>'Failed'];
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


    
    public function record(Request $request)
    {
       
        $startdate     = trim(strip_tags($request->startdate));
        $enddate       = trim(strip_tags($request->enddate));
        $searchapi     = trim(strip_tags($request->searchapi)); 
        $status        = trim(strip_tags($request->status));
        $userid        = trim(strip_tags($request->userid));
        $start         = trim(strip_tags($request->start));
        $length        = trim(strip_tags($request->length));
        $order         = trim(strip_tags($request->order)); 
        $search        = trim(strip_tags($request->search));
        $statename     = trim(strip_tags($request->statename));
        $operator     = trim(strip_tags($request->operator)); 
        if(empty($startdate) && empty($enddate)){
            $startdate = $this->today;
            $enddate   = $this->today;  
        } 
        if(!empty($startdate) && !empty($enddate)){ 
            $query = DB::table('users');
            $query->join('recharge', 'users.id', '=', 'recharge.userid'); 
            $query->join('transaction_cashdeposit as tb1', 'tb1.id', '=', 'recharge.txnid');
            $query->leftjoin('transaction_cashdeposit as tb2', 'tb2.id', '=', 'recharge.refundtxnid');
            $query->select(
                'recharge.id as id',
                'recharge.txnid as txnid',  
                'recharge.canumber as canumber',
                'recharge.operatorname as operatorname',
                'recharge.operatorid as operatorid', 
                'recharge.amount as amount', 
                'recharge.status as statusval',
                'recharge.status as status',
                'recharge.dateadded as addeddate',
                'users.username as username', 
                'recharge.comm as comm',      
                'tb1.cd_opening as opening',
                'tb1.cd_closing as closing',
                'tb1.narration as remarks',
                'tb1.amount as debit', 
                'tb2.amount  as credit',
                'tb2.comm  as commcredit',
                'recharge.updated_at as restime',
                );
          
             

             
            $query->whereDate('recharge.addeddate', '>=', $startdate);
            $query->whereDate('recharge.addeddate', '<=', $enddate);

            if ($status != "" ) {
                $query->where('recharge.status',$status);
            }

            if ($userid != "") {
               $query->where('recharge.userid',$userid);
            }
            

            (!empty($orderby) && !empty($order))? $query->orderBy('recharge.'.$orderby, $order): $query->orderBy("recharge.id", "desc");
                $query->where(function ($q) use ($search) {
                    if (!empty($search)) {
                        $q->orWhere('recharge.canumber', 'LIKE', "%{$search}%");
                        $q->orWhere('recharge.status', 'LIKE', "%{$search}%"); 
                        $q->orWhere('recharge.reqid', 'LIKE', "%{$search}%");
                        $q->orWhere('recharge.txnid', 'LIKE', "%{$search}%"); 
                    }
                    return $q;
                });
            
            
                if($request->user()->role == 5){
                $userid =  $request->user()->id;
                $query->where('recharge.userid',$userid);
            }
           
            $totaldata = $query->get()->toArray();
             
            $recordsTotal = $query->count();
            
             
            
            if ($length != "" && $start !="") {
                $data = $query->skip($start)->take($length)->get()->toArray();
                $recordsFiltered = count($data);
            }else{
                $data = $query->get()->toArray();
                $recordsFiltered = $query->count();
            }
            
            
            if(!empty($data)){
                foreach($data as $key=>$datum){  
                    if($datum->status){
                        $data[$key]->status =   $this->Stmtstatus[$datum->status];
                        $dateTime = new DateTime($datum->addeddate, new DateTimeZone('Asia/Kolkata'));  
                        // echo $dateTime->format("d/m/y  g:i A");
                        $data[$key]->addeddate =   $dateTime->format("d-m-Y  g:i:s A"); 

                    } 
                }
                return $this->response('success', ['message' => "Success.",'data' => $data,'recordsFiltered' => $recordsFiltered,'recordsTotal'    => $recordsTotal]); 
            }else{
                return $this->response('noresult', ['statuscode'=>200]); 
            }
            
        }else{
            $statuscode     = $this->statuscode['noresult'];
                $this->response = [
                    'statuscode'   => $statuscode,
                    'status'       => false,
                    'responsecode' => 0,
                    'msg'          => "Please add param.",
                    
                ];
                return response()->json($this->response, $statuscode);
            
        }
    }
}
