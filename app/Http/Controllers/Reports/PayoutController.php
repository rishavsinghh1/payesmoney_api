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
use App\Http\Traits\RechargeTrait;
use App\Libraries\Common\Guzzle;
use DateTime;
use DateTimeZone;
use Carbon\Carbon;
class PayoutController extends Controller
{
    use CommonTrait, HeaderTrait , RechargeTrait;
    public function __construct()
    {
        $this->today      = Carbon::now()->toDateString();
        $this->status = ['0'=>'Deleted','1'=>'Active','2'=>'Pending'];  
        $this->Stmtstatus = ['0'=>'Refund','1'=>'Success','2'=>'Process','3'=>'Failed','4'=>'Manual Refund'];
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
            $startdate = date('Y-m-d', strtotime("-30 days"));
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
                'recharge.refundtxnid as refundtxnid',
                'recharge.refid as orderid',  
                'recharge.canumber as canumber',
                'recharge.operatorname as operatorname',
                'recharge.operatorid as operatorid', 
                'recharge.amount as amount', 
                'recharge.status as statusval',
                'recharge.status as status',
                'recharge.api_status as api_status',
                'recharge.dateadded as addeddate',
                'users.username as username', 
                'users.firmname as firmname', 
                'recharge.comm as comm',      
                'recharge.sdcomm as sdcomm',      
                'recharge.dcomm as dcomm',     
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
            
           
            if($request->user()->role == 3){
                $userid =  $request->user()->id;
                $query->where('users.supdistributor',$userid);
            }
            if($request->user()->role == 4){
                $userid =  $request->user()->id;
                $query->where('users.distributor',$userid);
            }
            if($request->user()->role == 5){
                $userid =  $request->user()->id;
                $query->where('users.id',$userid);
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
         
            if($request->user()->role == 1 || $request->user()->role == 1){
                $head           = HEADERTrait::txn_ADMIN_header();
            }else if($request->user()->role == 3){
                $head           = HEADERTrait::txn_SD_header();
            }else if($request->user()->role == 4){
                $head           = HEADERTrait::txn_DIST_header();
            }else{
                $head           = HEADERTrait::txn_REATILER_header();
            }
            
            if(!empty($data)){
                foreach($data as $key=>$datum){  
                    // if($datum->credit){
                    //     $data[$key]->credits =   $datum->credit+$datum->commcredit;
                    // }
                    if($datum->status){
                       $data[$key]->status =   $this->Stmtstatus[$datum->status];
                        $dateTime = new DateTime($datum->addeddate, new DateTimeZone('Asia/Kolkata'));  
                        $data[$key]->addeddate =   $dateTime->format("d-m-Y  g:i:s A"); 

                    } 
                }
                return $this->response('success', ['message' => "Success.",'header' => $head,'data' => $data,'recordsTotal'=> $recordsTotal]); 
            
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
    public function ledgerrecord(Request $request)
    {
        //$post=$request->all();
       
        $startdate    = trim(strip_tags($request->startdate));
        $enddate      = trim(strip_tags($request->enddate));  
        $status       = trim(strip_tags($request->status)); 
        $start        = trim(strip_tags($request->start));
        $length       = trim(strip_tags($request->length));
        $order         = trim(strip_tags($request->order));
        $orderby       = trim(strip_tags($request->orderby));
        $userid       = trim(strip_tags($request->userid));
        $search        = trim(strip_tags($request->search));
        if(empty($startdate) && empty($enddate)){
            $startdate = date('Y-m-d');
            $enddate   = $this->today;  
        }
        $userdata = Auth::user();
        $query = DB::table('users');  
        if($userdata->role == 1){
            $userid =  $userdata->id;
            $request=  ['transaction_cashdeposit.id','users.username','transaction_cashdeposit.sopening as cd_opening','transaction_cashdeposit.amount','transaction_cashdeposit.comm','transaction_cashdeposit.dcomm','transaction_cashdeposit.sdcomm','transaction_cashdeposit.tds','transaction_cashdeposit.sclosing as cd_closing','transaction_cashdeposit.stype','transaction_cashdeposit.narration',
                'transaction_cashdeposit.remarks','transaction_cashdeposit.ttype','transaction_cashdeposit.dateadded','transaction_cashdeposit.customercharge',
                DB::raw('(CASE WHEN tbl_transaction_cashdeposit.stype="debit" THEN tbl_transaction_cashdeposit.amount END) AS debit'),
                DB::raw('(CASE WHEN tbl_transaction_cashdeposit.stype="credit" THEN tbl_transaction_cashdeposit.amount END) AS credit')
            ];
            $query->leftjoin('transaction_cashdeposit', 'transaction_cashdeposit.sid', '=', 'users.id');
            $query->where('transaction_cashdeposit.ttype','<>',6);
        }elseif($userdata->role == 3){
            $userid =  $userdata->id;
            $request=  ['transaction_cashdeposit.id','users.username','transaction_cashdeposit.sdopening as cd_opening','transaction_cashdeposit.amount','transaction_cashdeposit.sdcomm','transaction_cashdeposit.gst','transaction_cashdeposit.tds','transaction_cashdeposit.sdclosing as cd_closing','transaction_cashdeposit.sdtype','transaction_cashdeposit.narration','transaction_cashdeposit.remarks','transaction_cashdeposit.ttype','transaction_cashdeposit.dateadded',
            'transaction_cashdeposit.customercharge',
            DB::raw('(CASE WHEN tbl_transaction_cashdeposit.sdtype="debit" THEN tbl_transaction_cashdeposit.amount END) AS debit'),
            DB::raw('(CASE WHEN tbl_transaction_cashdeposit.sdtype="credit" THEN tbl_transaction_cashdeposit.amount END) AS credit')];
            $query->where('transaction_cashdeposit.sdid',$userid);
            $query->where('transaction_cashdeposit.ttype','<>',6);
            $query->leftjoin('transaction_cashdeposit', 'transaction_cashdeposit.sdid', '=', 'users.id');
        }elseif($userdata->role == 4){
            $userid =  $userdata->id;
            $request=  ['transaction_cashdeposit.id',
            'users.username','transaction_cashdeposit.dopening as cd_opening',
            'transaction_cashdeposit.amount',
            'transaction_cashdeposit.dcomm',
            'transaction_cashdeposit.gst',
            'transaction_cashdeposit.tds',
            'transaction_cashdeposit.dclosing as cd_closing',
            'transaction_cashdeposit.dtype',
            'transaction_cashdeposit.narration',
            'transaction_cashdeposit.remarks',
            'transaction_cashdeposit.ttype',
            'transaction_cashdeposit.utype', 
            'transaction_cashdeposit.dateadded',
            'transaction_cashdeposit.customercharge',
             DB::raw('(CASE WHEN tbl_transaction_cashdeposit.dtype="debit" THEN tbl_transaction_cashdeposit.amount END) AS debit'),
            DB::raw('(CASE WHEN tbl_transaction_cashdeposit.dtype="credit" THEN tbl_transaction_cashdeposit.amount END) AS credit')];
            $query->where('transaction_cashdeposit.did',$userid);
            $query->where('transaction_cashdeposit.ttype','<>',6);
            $query->leftjoin('transaction_cashdeposit', 'transaction_cashdeposit.did', '=', 'users.id');
        } 
        elseif($userdata->role == 5){
            $userid =  $userdata->id;
            $request=  ['transaction_cashdeposit.id','users.username','transaction_cashdeposit.cd_opening','transaction_cashdeposit.amount','transaction_cashdeposit.comm','transaction_cashdeposit.gst','transaction_cashdeposit.tds','transaction_cashdeposit.cd_closing','transaction_cashdeposit.utype',
            'transaction_cashdeposit.narration','transaction_cashdeposit.ttype','transaction_cashdeposit.dateadded' , 
            'transaction_cashdeposit.customercharge',
            DB::raw('(CASE WHEN tbl_transaction_cashdeposit.ttype= 6 THEN tbl_transaction_cashdeposit.amount END) AS debit'),DB::raw('(CASE WHEN tbl_transaction_cashdeposit.ttype= 0 THEN tbl_transaction_cashdeposit.amount END) AS credit')];
            $query->where('transaction_cashdeposit.uid',$userid);
            $query->leftjoin('transaction_cashdeposit', 'transaction_cashdeposit.uid', '=', 'users.id');
        } 
          
        
        $query->select($request);  
        $query->whereBetween('transaction_cashdeposit.addeddate', [$startdate, $enddate]);  
            if($order != ""){
                $query->orderBy('transaction_cashdeposit.id', $order);
                
            }else{
                $query->orderBy('transaction_cashdeposit.id','DESC');
            }
            (!empty($orderby) && !empty($order))? $query->orderBy('transaction_cashdeposit.'.$orderby, $order): $query->orderBy("transaction_cashdeposit.id", "desc");
                $query->where(function ($q) use ($search) {
                    if (!empty($search)) {
                        $q->orWhere('transaction_cashdeposit.username', 'LIKE', "%{$search}%");
                        $q->orWhere('transaction_cashdeposit.amount', 'LIKE', "%{$search}%");  
                    }
                    return $q;
                }); 
            $recordsTotal = $query->count(); 
            if ($length != "" && $start !="") {
                $data = $query->skip($start)->take($length)->get()->toArray();
                $recordsFiltered = count($data);
            }else{
                $data = $query->get()->toArray();
                $recordsFiltered = $query->count();
            } 
            if($userdata->role == 1){
                $head           = HEADERTrait::txn_ledger_admin_header();
            }else if($userdata->role == 3){
                $head           = HEADERTrait::txn_ledger_admin_header();
            }else if($userdata->role == 4){
                $head           = HEADERTrait::txn_ledger_DIST_header();
            }else{
                $head           = HEADERTrait::txn_ledger_admin_header();
            }
            
            if(!empty($data)){
               
                foreach($data as $key=>$datum){    
                        $data[$key]->txnid = $datum->id;
                        $data[$key]->ttype= RechargeTrait::txn_type($datum->ttype);  
                        $dateTime = new DateTime($datum->dateadded, new DateTimeZone('Asia/Kolkata'));  
                        $data[$key]->dateadded =   $dateTime->format("d-m-Y  g:i:s A");  
                }
                //dd($data);
                return $this->response('success', ['message' => "Success.",'header' => $head,'data' => $data,'recordsFiltered' => $recordsFiltered,'recordsTotal'=> $recordsTotal]); 
            }else{
                return $this->response('noresult', ['statuscode'=>200]); 
            }
    }
    public function dayledger(Request $request)
    {
        DB::statement("SET SQL_MODE=''");
        $startdate     = trim(strip_tags($request->startdate));
        $enddate       = trim(strip_tags($request->enddate)); 
        $length        = trim(strip_tags($request->length));
        $userid        = trim(strip_tags($request->userid));
        $status       = trim(strip_tags($request->status)); 
        $start = empty($startdate)? $this->today:$startdate;
        $end   = empty($enddate)? $this->today:$enddate;
        $status   = empty($status)? 1:$status;
         
        $userdata = Auth::user();
        $query = DB::table('recharge'); 
        if($userdata->role == 1){
            $userid =  $userdata->id;
            if($status == 1){
                $request=  [DB::raw('tbl_recharge.operatorname as opname,COUNT(tbl_recharge.id) as totalcount, 
                SUM(tbl_recharge.amount) as totalsale, 
                SUM(tbl_recharge.comm)  as RTCOMM,
                SUM(tbl_recharge.dcomm) as DISTCOMM,
                SUM(tbl_recharge.sdcomm) as SDCOMM, 
                ROUND((SUM(tbl_recharge.comm)+SUM(tbl_recharge.dcomm)+SUM(tbl_recharge.sdcomm))/SUM(tbl_recharge.amount)*100,2) as commission,
                SUM(tbl_recharge.comm)+SUM(tbl_recharge.dcomm)+SUM(tbl_recharge.sdcomm) as totalcomm,
                SUM(tbl_recharge.amount) - (SUM(tbl_recharge.comm)+SUM(tbl_recharge.dcomm)+SUM(tbl_recharge.sdcomm)) as salemcomm,
                tbl_recharge.addeddate as date
                ')];
            }elseif($status == 2){ 
                $request=  [DB::raw('SUM(tbl_recharge.amount) as totalsale,COUNT(tbl_recharge.id) as totalcount,tbl_recharge.operatorname as opname,tbl_recharge.addeddate as date')];
            }else{
                $request=  [DB::raw('SUM(tbl_recharge.amount) as totalsale,
                COUNT(tbl_recharge.id) as totalcount,
                tbl_recharge.operatorname as opname,
                tbl_recharge.addeddate as date')];
            }
            $query->where('transaction_cashdeposit.sid',$userid);
        }elseif($userdata->role == 3){
            $userid =  $userdata->id;
            if($status == 1){
                $request=  [DB::raw('tbl_recharge.operatorname as opname,COUNT(tbl_recharge.id) as totalcount, 
                SUM(tbl_recharge.amount) as totalsale, 
                SUM(tbl_recharge.comm)  as RTCOMM,
                SUM(tbl_recharge.dcomm) as DISTCOMM,
                SUM(tbl_recharge.sdcomm) as SDCOMM, 
                SUM(tbl_recharge.comm)+SUM(tbl_recharge.dcomm)+SUM(tbl_recharge.sdcomm) as totalcomm,
                SUM(tbl_recharge.amount) - (SUM(tbl_recharge.comm)+SUM(tbl_recharge.dcomm)+SUM(tbl_recharge.sdcomm)) as salemcomm,
                tbl_recharge.addeddate as date')];
            }elseif($status == 2){ 
                $request=  [DB::raw('SUM(tbl_recharge.amount) as totalsale,COUNT(tbl_recharge.id) as totalcount,tbl_recharge.operatorname as opname,tbl_recharge.addeddate as date')];
            }else{
                $request=  [DB::raw('SUM(tbl_recharge.amount) as totalsale,COUNT(tbl_recharge.id) as totalcount,tbl_recharge.operatorname as opname,tbl_recharge.addeddate as date')];
            }
            $query->where('transaction_cashdeposit.sdid',$userid);
        }elseif($userdata->role == 4){
            $userid =  $userdata->id;
            if($status == 1){
                $request=  [DB::raw('tbl_recharge.operatorname as opname,COUNT(tbl_recharge.id) as totalcount, 
                SUM(tbl_recharge.amount) as totalsale, 
                SUM(tbl_recharge.comm)  as RTCOMM,
                SUM(tbl_recharge.dcomm) as DISTCOMM, 
                SUM(tbl_recharge.comm)+SUM(tbl_recharge.dcomm) as totalcomm,
                SUM(tbl_recharge.amount) - (SUM(tbl_recharge.comm)+SUM(tbl_recharge.dcomm)) as salemcomm,
                tbl_recharge.addeddate as date')];
            }elseif($status == 2){ 
                $request=  [DB::raw('SUM(tbl_recharge.amount) as totalsale,COUNT(tbl_recharge.id) as totalcount,tbl_recharge.operatorname as opname,tbl_recharge.addeddate as date')];
            }else{
                $request=  [DB::raw('SUM(tbl_recharge.amount) as totalsale,COUNT(tbl_recharge.id) as totalcount,tbl_recharge.operatorname as opname,tbl_recharge.addeddate as date')];
            }
            $query->where('transaction_cashdeposit.did',$userid);
        }elseif($userdata->role == 5){
            $userid =  $userdata->id;
            
            if($status == 1){
                $request=  [DB::raw('tbl_recharge.operatorname as opname,COUNT(tbl_recharge.id) as totalcount, 
                SUM(tbl_recharge.amount) as totalsale, 
                SUM(tbl_recharge.comm)  as RTCOMM,  
                SUM(tbl_recharge.comm) as totalcomm,
                SUM(tbl_recharge.amount) - (SUM(tbl_recharge.comm)) as salemcomm,
                tbl_recharge.addeddate as date')];
            }elseif($status == 2){ 
                $request=  [DB::raw('tbl_recharge.operatorname as opname,COUNT(tbl_recharge.id) as totalcount, 
                SUM(tbl_recharge.amount) as totalsale, 
                SUM(tbl_recharge.comm)  as RTCOMM,  
                SUM(tbl_recharge.comm) as totalcomm,
                SUM(tbl_recharge.amount) - (SUM(tbl_recharge.comm)) as salemcomm,
                tbl_recharge.addeddate as date')];
            }else{
                $request=  [DB::raw('tbl_recharge.operatorname as opname,COUNT(tbl_recharge.id) as totalcount, 
                SUM(tbl_recharge.amount) as totalsale, 
                SUM(tbl_recharge.comm)  as RTCOMM,  
                SUM(tbl_recharge.comm) as totalcomm,
                SUM(tbl_recharge.amount) - (SUM(tbl_recharge.comm)) as salemcomm,
                tbl_recharge.addeddate as date')];
            }
            $query->where('transaction_cashdeposit.uid',$userid);
        }
        
        $query->leftjoin('transaction_cashdeposit', 'transaction_cashdeposit.id', '=', 'recharge.txnid'); 
        $query->select($request);   
        $query->whereBetween('transaction_cashdeposit.addeddate', [$startdate, $enddate]); 
        // $query->whereDate('recharge.addeddate', '>=', $startdate);
        // $query->whereDate('recharge.addeddate', '<=', $enddate);  
            $query->where('recharge.status',$status); 
             $query->groupBy('operatorname');  
             $query->orderByRaw('COUNT("recharge.id") DESC');
             $recordsTotal = $query->count(); 
            $data = $query->get()->toArray();
           // dd($data);
            if($userdata->role == 1){
                $head           = HEADERTrait::txn_S_Daybook_header();
            }else if($userdata->role == 3){
                $head           = HEADERTrait::txn_SD_Daybook_header();
            }else if($userdata->role == 4){
                $head           = HEADERTrait::txn_DIST_Daybook_header();
            }else if($userdata->role == 5){
                $head           = HEADERTrait::txn_RT_Daybook_header();
            } 
            if (!empty($data)) {
                $totalcount=0;
                $totalsale=0;
                $totalsalemcomm=0;
                $totalcommission=0; 
                $totalRTCOMM = 0;
                $totalSDCOMM = 0;
                $totalDISTCOMM = 0;
                foreach($data as $key=>$datum){  
                        $totalcount +=  $datum->totalcount;
                        $totalsale +=  $datum->totalsale; 
                                $totalsalemcomm +=  $datum->salemcomm; 
                                $totalcommission +=  $datum->totalcomm;
                                $totalRTCOMM += empty($datum->RTCOMM)?'0':$datum->RTCOMM;
                                $totalSDCOMM += empty($datum->SDCOMM)?'0':$datum->SDCOMM;
                                $totalDISTCOMM += empty($datum->DISTCOMM)?'0':$datum->DISTCOMM;
                         
                        $dateTime = new DateTime($datum->date, new DateTimeZone('Asia/Kolkata'));  
                        $data[$key]->date =   $dateTime->format("d-m-Y  g:i:s A");  
                }
                return $this->response('success', ['message' => "Success.",'header' => $head,
                        'data' => $data,
                        'recordsTotal'=> $recordsTotal,
                        'total' => [
                        'totalcount'=>$totalcount,
                        'totalsale'=>$totalsale,
                        'totalsalemcomm'=>$totalsalemcomm,
                        'totalcommission'=>$totalcommission,
                        'totalrtcomm'=>$totalRTCOMM,
                        'totalsdcomm'=>$totalSDCOMM,
                        'totaldistcomm'=>$totalDISTCOMM
                ]
                ]); 
            
            } else {
                return $this->response('noresult', ['statuscode'=>200]); 
            } 
    }

    public function refundReport(Request $request){
        $startdate     = trim(strip_tags($request->startdate));
        $enddate       = trim(strip_tags($request->enddate)); 
        $status        = trim(strip_tags($request->status));
        $userid        = trim(strip_tags($request->userid));
        $start         = trim(strip_tags($request->start));
        $length        = trim(strip_tags($request->length));
        $order         = trim(strip_tags($request->order)); 
        $search        = trim(strip_tags($request->search));
        $statename     = trim(strip_tags($request->statename));
        $operator     = trim(strip_tags($request->operator)); 
        if(empty($startdate) && empty($enddate)){
            $startdate = date('Y-m-d');
            $enddate   = $this->today;  
        } 
        if(!empty($startdate) && !empty($enddate)){  
            $userdata = Auth::user();
            $query = DB::table('users');
            $query->join('refund_transaction', 'users.id', '=', 'refund_transaction.uid'); 
            $query->join('transaction_cashdeposit as tb1', 'tb1.id', '=', 'refund_transaction.txn_id');
            $query->leftjoin('transaction_cashdeposit as tb2', 'tb2.id', '=', 'refund_transaction.refundtxnid');
            $query->select(
                'refund_transaction.id as id',
                'refund_transaction.txn_id as txn_id',
                'refund_transaction.refundtxnid as refundtxnid',
                'refund_transaction.order_id as orderid',  
                'refund_transaction.canumber as canumber', 
                'refund_transaction.amount as amount',  
                'refund_transaction.status as status', 
                'refund_transaction.addeddate as addeddate',
                'refund_transaction.created_at as refunddate',
                'users.username as username', 
                'users.firmname as firmname',       
                'refund_transaction.sdcomm as sdcomm',      
                'refund_transaction.dcomm as dcomm',     
                'tb1.cd_opening as opening',
                'tb1.cd_closing as closing',
                'tb2.narration as remarks',
                'tb1.amount as debit', 
                'tb2.amount  as credit',  
                );  
            // $query->whereDate('refund_transaction.addeddate', '>=', $startdate);
            // $query->whereDate('refund_transaction.addeddate', '<=', $enddate);
            $query->where(function ($q) use ($startdate, $enddate) {
                if (!empty($startdate) && !empty($enddate)) {
                    $q->whereRaw("date(tbl_refund_transaction.created_at) between '{$startdate}' and '{$enddate}'");
                }
                return $q;
            });
             

            if ($status != "" ) {
                $query->where('refund_transaction.status',$status);
            }

            if ($userid != "") {
               $query->where('refund_transaction.userid',$userid);
            }
            
            (!empty($orderby) && !empty($order))? $query->orderBy('refund_transaction.'.$orderby, $order): $query->orderBy("refund_transaction.id", "desc");
            $query->where(function ($q) use ($search) {
                if (!empty($search)) {
                    $q->orWhere('refund_transaction.canumber', 'LIKE', "%{$search}%");
                    $q->orWhere('refund_transaction.status', 'LIKE', "%{$search}%"); 
                    $q->orWhere('refund_transaction.order_id', 'LIKE', "%{$search}%");
                    $q->orWhere('refund_transaction.refundtxnid', 'LIKE', "%{$search}%"); 
                }
                return $q;
            });
        
       
        if($request->user()->role == 3){
            $userid =  $request->user()->id;
            $query->where('users.supdistributor',$userid);
        }
        if($request->user()->role == 4){
            $userid =  $request->user()->id;
            $query->where('users.distributor',$userid);
        }
        if($request->user()->role == 5){
            $userid =  $request->user()->id;
            $query->where('users.id',$userid);
        }
    
        if ($length != "" && $start !="") {
            $data = $query->skip($start)->take($length)->get()->toArray();
            $recordsFiltered = count($data);
        }else{
            $data = $query->get()->toArray();
            $recordsFiltered = $query->count();
        } 
        if($userdata->role == 1){
            $head           = HEADERTrait::txn_Refundledger_Super_header();
        }else if($userdata->role == 3){
            $head           = HEADERTrait::txn_Refundledger_SD_header();
        }else if($userdata->role == 4){
            $head           = HEADERTrait::txn_Refundledger_DIST_header();
        }else{
            $head           = HEADERTrait::txn_Refundledger_Super_header();
        }
        
        if(!empty($data)){
            $totalsdrefund=0;
            $totaldistrefund=0;
            $totalamount = 0;
            foreach($data as $key=>$datum){    
                    $totalsdrefund +=  $datum->sdcomm; 
                    $totaldistrefund +=  $datum->dcomm; 
                    $totalamount +=  $datum->amount; 
                    $data[$key]->txnid = $datum->id; 
                    $dateTime = new DateTime($datum->refunddate, new DateTimeZone('Asia/Kolkata'));  
                    $data[$key]->refunddate =   $dateTime->format("d-m-Y  g:i:s A");  
                    $data[$key]->txn_id  = 'PMYT0023'.$datum->txn_id;
                    $data[$key]->refundtxnid  = 'PMYR0023'.$datum->refundtxnid;
            }
            //dd($data);
            return $this->response('success', ['message' => "Success.",
            'header' => $head,'data' => $data,'recordsFiltered' => $recordsFiltered,'recordsTotal'=> $recordsFiltered,
            'total' => [
                'totalsdrefund'=>$totalsdrefund,
                'totaldistrefund'=>$totaldistrefund,
                'totalamount'=>$totalamount 
        ]
        ]); 
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
