<?php

namespace App\Http\Controllers\Commission;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Traits\CommonTrait;
use App\Http\Traits\CommissionTrait;
use App\Models\CommissionTemplate;
use App\Models\CommissionModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Traits\HeaderTrait;
use Illuminate\Support\Facades\Auth;
class Commission extends Controller
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

    public function getuserlist(Request $request){
        $userdata = Auth::user();   
        if(in_array($userdata->role,[1,2])){
            try { 
                $validated = Validator::make($request->all(), [
                    'search'   => 'required',
                ]);
                if ($validated->fails()) {
                    $message   = $this->validationResponse($validated->errors());
                    return $this->response('validatorerrors', $message);
                }
                $search = []; 
                if($request->usertype){
                    $search['usertype'] = 5;
                }
               
                $search = $request->search;
                $query  = User::query();
                $query->select("id", "fullname", "email", "phone", "status", "username","firmname");
                //$query->where("user_type", );
                $query->where(function ($q) use ($search) {
                    if (!empty($search)) {
                        $q->orWhere('username', 'LIKE', "%{$search}%");
                        $q->orWhere('fullname', 'LIKE', "%{$search}%"); 
                        $q->orWhere('firmname', 'LIKE', "%{$search}%"); 
                        $q->orWhere('email', 'LIKE', "%{$search}%");
                        $q->orWhere('phone', 'LIKE', "%{$search}%");
                    }
                    return $q;
                });
                $users = $query->get(); 
              
                if ($users->isNotEmpty()) {
                   
                    $results = [];
                    foreach ($users as $key => $value) {
                       
                        $results[$key]["userid"] = $value["id"];
                        $results[$key]["user"]   = $value["username"] . "|" . $value["fullname"]. "|" .$value["firmname"];
                    } 
                    $response = [  
                        'data' => $results,
                        'message' => "User Listed successfully"
                    ]; 
                    return $this->response('success', $response); 
                }else{
                    $response = [
                        'errors' => "invalid!",
                        'message' => 'Data not found!'
                    ];
                    return $this->response('notvalid', $response);  
                }
            } catch (\Exception $e) {
                return  $this->response('internalservererror', ['message' => $e->getMessage()]);
            }
        }else{

        }
    }

    public function getassignedCommission(Request $request){
        $userdata = Auth::user();   
        if(in_array($userdata->role,[1,2])){
            try{
                $validated = Validator::make($request->all(), [
                    'type'   => 'required',
                    'userid'   => 'required',
                ]);
                if ($validated->fails()) {
                    $message   = $this->validationResponse($validated->errors());
                    return $this->response('validatorerrors', $message);
                }

                 
                $results = $this->signlequery('commission_template',["type"=>$request->type]);
               // $reqs = 'id,tempid,type,userid';
                $rs = $this->signlequery_temp('commission',["type"=>$request->type,"userid"=>$request->userid]);
                $data1 = []; 
                if(!empty($results)){
                    foreach($results as $info){             
                        $sub_array = [];
                        $sub_array['id']        =   $info->id;
                        $sub_array['name']      =   $info->name;
                        $sub_array['commission']=   $info->commission;
                        $sub_array['type']      =   $this->type[$info->type];
                        $sub_array['status']    =   $this->status[$info->status];  
                        $data1[] = $sub_array; 
                    } 
                    $response = [
                        'message'    => "Plan fetched successfully.",
                        'data'       => $data1,
                        'assignplan' => $rs
                    ];
                    return $this->response('success', $response); 
                }else{
                    $response = [
                        'errors' => "invalid!",
                        'message' => $response['message']
                    ];
                    return $this->response('notvalid', $response);  
                }

            } catch (\Exception $e) {
                return  $this->response('internalservererror', ['message' => $e->getMessage()]);
            }
        }else{

        }
    }

    public function assigntempcomm(Request $request){
        $userdata = Auth::user();   
        if(in_array($userdata->role,[1,2])){
            try{
                $validated = Validator::make($request->all(), [
                    'type'    => 'required',
                    'userid'  => 'required',
                    'tempid'  => 'required'
                ]);
                if ($validated->fails()) {
                    $message   = $this->validationResponse($validated->errors());
                    return $this->response('validatorerrors', $message);
                }
                $post = [
                    'userid' => $request->userid,
                    'type'   => $request->type,
                    'tempid' => $request->tempid,
                ];
                $createcommission = CommissionModel::insert($post);  
                if($createcommission){
                    $response = [
                        'message' => "Plan assigned sucesssfully."
                    ];
                    return $this->response('success', $response);
                }else{
                    $response = [
                        'errors' => "invalid!",
                        'message' => "Please try again later!"
                    ];
                    return $this->response('notvalid', $response);
                }
            } catch (\Exception $e) {
                return  $this->response('internalservererror', ['message' => $e->getMessage()]);
            }
        }
    }
}