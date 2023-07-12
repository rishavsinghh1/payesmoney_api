<?php

namespace App\Http\Controllers\Companybank;
use App\Models\User;
use App\Models\CompanyBank;
use App\Http\Controllers\Controller;
use App\Http\Traits\CommonTrait;   
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Traits\HeaderTrait;
use Illuminate\Support\Facades\Auth;
use DateTime;
use DateTimeZone;
use Carbon\Carbon;
class BankController extends Controller
{
    use CommonTrait,HeaderTrait;
    public function __construct() { 
        $this->today      = Carbon::now()->toDateString();
        $this->status 	= ['Inactive','Active'];
        $this->ctype 	= ['Percentage','Fixed'];
    }

    public function getlist(Request $request){
        $userdata = Auth::user();   
        if(in_array($userdata->role,[1,2])){
            try { 
                $startdate     = trim(strip_tags($request->startdate));
                $enddate       = trim(strip_tags($request->enddate)); 
                $start         = trim(strip_tags($request->start));
                $length        = trim(strip_tags($request->length));
                $order         = trim(strip_tags($request->order)); 
                $search        = trim(strip_tags($request->search));  
                $searchby     = trim(strip_tags($request->searchby));
                $searchvalue     = trim(strip_tags($request->searchvalue));
                
                $query = DB::table('companybank');
                    $query->select( 
                    'id', 
                    'name',
                    'accno','balance','ifsc','branch','status','added'
                    );
                    if(!empty($startdate) && !empty($enddate)){ 
                        $query->where(function ($q) use ($startdate, $enddate) {
                            if (!empty($startdate) && !empty($enddate)) {
                                $q->whereRaw("date(added) between '{$startdate}' and '{$enddate}'"); 
                            }
                            return $q;
                        });
                    }
                $totaldata = $query->get()->toArray();
                $recordsTotal = $query->count();
                 
                $query->orderBy('added','desc');
                
                if ($length != "" && $start !="") {
                    $data = $query->skip($start)->take($length)->get()->toArray();
                    $recordsFiltered = count($data);
                }else{
                    $data = $query->get()->toArray();
                    $recordsFiltered = $query->count();
                } 
               
                if($recordsFiltered > 0){
                    $response = [
                        'message' => "Data Found",
                        //'header'            => $head,
                        'data'              => $data,
                        'recordsFiltered'   => $recordsTotal,
                        'recordsTotal'      => $recordsFiltered,
                    ];  
                    return $this->response('success', $response);
                }else{
                    $response = [
                        'errors' => "invalid!",
                        'message' => "No result Found."
                    ];
                    return $this->response('notvalid', $response); 
                }
                
                 
            } catch (\Exception $e) {
                return  $this->response('internalservererror', ['message' => $e->getMessage()]);
            }
        }else{

        }
    }

    public function add(Request $request){
        $userdata = Auth::user();   
        if(in_array($userdata->role,[1,2])){
            try { 
                $validated = Validator::make($request->all(), [
                    'bankname'   => 'required',
                    'accno'   => 'required',
                    'balance'   => 'required',
                    'ifsc'   => 'required',
                    'branch'   => 'required',
                    'status'   => 'required',
                ]);
                if ($validated->fails()) {
                    $message   = $this->validationResponse($validated->errors());
                    return $this->response('validatorerrors', $message);
                }
                $requestdata  =   array(
                    "name"                  =>  strtoupper($request->bankname),
                    "accno"                 =>  $request->accno,
                    "balance"               =>  $request->balance,
                    "ifsc"                  =>  strtoupper($request->ifsc),
                    "branch"                =>  strtoupper($request->branch),
                    "status"                => $request->status, 
                );
                $requestdata = new CommissionTemplate();
                $requestdata->name = strtoupper($request->name);
                $requestdata->accno = $request->accno;
                $requestdata->balance = $request->balance;
                $requestdata->ifsc = strtoupper($request->ifsc); 
                $requestdata->branch =  strtoupper($request->branch);
                $requestdata->status = $request->status;
                $requestdata->save();
                if($requestdata){
                    return $this->response('success',['message' => 'Bank Added successfully!']);
                }else{
                    return $this->response('incorrectinfo');
                }
                  
            } catch (\Exception $e) {
                return  $this->response('internalservererror', ['message' => $e->getMessage()]);
            }
        }else{

        }
    }

    public function getbyid(Request $request){
        $userdata = Auth::user();   
        if(in_array($userdata->role,[1,2])){
            try { 
                $validated = Validator::make($request->all(), [
                    'bankid'   => 'required', 
                ]);
                if ($validated->fails()) {
                    $message   = $this->validationResponse($validated->errors());
                    return $this->response('validatorerrors', $message);
                }
                $startdate     = trim(strip_tags($request->startdate));
                $enddate       = trim(strip_tags($request->enddate)); 
                $start         = trim(strip_tags($request->start));
                $length        = trim(strip_tags($request->length));
                $order         = trim(strip_tags($request->order)); 
                $search        = trim(strip_tags($request->search));  
                $searchby     = trim(strip_tags($request->searchby));
                $searchvalue     = trim(strip_tags($request->searchvalue));
                
                $query = DB::table('companybank');
                    $query->select( 
                    'id', 
                    'name',
                    'accno','balance','ifsc','branch','status','added'
                    );
                    if(!empty($startdate) && !empty($enddate)){ 
                        $query->where(function ($q) use ($startdate, $enddate) {
                            if (!empty($startdate) && !empty($enddate)) {
                                $q->whereRaw("date(added) between '{$startdate}' and '{$enddate}'"); 
                            }
                            return $q;
                        });
                    }
                $query->where('id',$request->bankid);
                $totaldata = $query->get()->toArray();
                $recordsTotal = $query->count();
                 
                $query->orderBy('added','desc');
                
                if ($length != "" && $start !="") {
                    $data = $query->skip($start)->take($length)->get()->toArray();
                    $recordsFiltered = count($data);
                }else{
                    $data = $query->get()->toArray();
                    $recordsFiltered = $query->count();
                } 
               
                if($recordsFiltered > 0){
                    $response = [
                        'message' => "Data Found",
                        //'header'            => $head,
                        'data'              => $data,
                        'recordsFiltered'   => $recordsTotal,
                        'recordsTotal'      => $recordsFiltered,
                    ];  
                    return $this->response('success', $response);
                }else{
                    $response = [
                        'errors' => "invalid!",
                        'message' => "No result Found."
                    ];
                    return $this->response('notvalid', $response); 
                }
                
                 
            } catch (\Exception $e) {
                return  $this->response('internalservererror', ['message' => $e->getMessage()]);
            }
        }else{

        }
    }
     
}