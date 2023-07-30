<?php

namespace App\Http\Controllers\User;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Traits\CommonTrait;
use App\Http\Traits\HeaderTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
class UserController extends Controller
{
    use CommonTrait,HeaderTrait;
    public function __construct() {
        $this->status = ['0' => "Pending", '1' => "Active", '2' => "Deactive"];
    }
    public function listUsers(Request $request)
    {
        try {
            $search = $request->searchvalue;
            $orderby= $request->orderby;
            $order  = $request->order;
            $searchColumn = ['users.id', 'users.status', 'users.fullname', 'users.username', 'users.email', 'users.phone', 'users.role','roles.name'];
            $select = ['users.id as user_id', 'users.status as status', 'users.fullname', 'users.username', 'users.email', 'users.phone', 'users.role as role_id','users.created_at','roles.name as role'];
            $query = User::select($select)->join('roles', 'roles.id', '=', 'users.role');
            $totalCount = $query->count();
            if(!empty($search)){
                $query->where(function($query) use ($searchColumn, $search){
                    foreach($searchColumn as $column){
                        $query->orwhere($column, 'like', '%' .  trim($search) . '%');
                    }
                });                
            }
            (!empty($orderby) && !empty($order))? $query->orderBy("users.".$orderby, $order): $query->orderBy("users.id", "desc");
            $length = (!empty($request->length))? $request->length: 20;
            $start  = (!empty($request->start))? $request->start: 0;
            $list   = $query->skip($start)->take($length)->get();
            foreach($list as $key => $val){
                $list[$key]->status = $this->status[$val->status];
                $list[$key]->created = date('d-m-Y',strtotime($val->created_at));
            }
            $count  = count($list);
            $header = $this->users();
            $details = [
                "message" => "List fetched",
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
    public function getUser(Request $request)
    {
        try {
             
            $user = Auth::user(); 
            if($user){
                return $this->response('success',['data' => $user, 'message' => 'Details fetched successfully!']);
            }else{
                return $this->response('incorrectinfo');
            }
             
        } catch (\Exception $e) {
            return  $this->response('internalservererror', ['message' => $e->getMessage()]);
        }
    }

    public function updateUser(Request $request)
    {
        try {
            $validated = Validator::make($request->all(), [
                'user_id'   => 'required',
                'fullname'  => 'required',
                "email"     => 'required|email|min:8|max:50',
                "phone"     => 'required|digits:10',
                "role"      => 'required|numeric|exists:roles,id',
                "status"    => 'required'
            ]);
            if ($validated->fails()) {
                $message   = $this->validationResponse($validated->errors());
                return $this->response('validatorerrors', $message);
            }

            $userwithsameMobile =  User::select('id')->where("phone", $request['phone'])->first();
            if (!empty($userwithsameMobile) && $userwithsameMobile->id != $request['user_id']) {
                return  $this->response('notvalid', ['message' => 'Mobile number already exist !']);
            }

            $userwithsameEmail =  User::select('id')->where("email", $request['email'])->first();
            if (!empty($userwithsameEmail) && $userwithsameEmail->id != $request['user_id']) {
                return  $this->response('notvalid', ['message' => 'Email address already taken !']);
            }

            $user               = User::find($request['user_id']);
            $user->fullname     = $request->fullname;
            $user->email        = $request->email;
            $user->phone        = $request->phone;
            $user->role         = $request->role;
            $user->status       = $request->status;
            $result = $user->update();
            if ($result) {
                return $this->response('success', ['message' => 'user updated successfully']);
            } else {
                return  $this->response('apierror', ['message' => 'Something went wrong!']);
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function searchUser(Request $request)
    {
        try {
            $search = $request->input('searchvalue');
            if($search && strlen($search) > 3){
                $users = DB::connection('pgsql')->table('users')->select('id','fullname','email','phone','username')
                ->where(function($query) use ($search){
                    $query->where('fullname', 'like',  $search . '%');
                    $query->orWhere('email', 'like',  $search . '%');
                    $query->orWhere('phone', 'like',  $search . '%');
                    $query->orWhere('username', 'like',  $search . '%');
                })->where('status',1)->orderBy('id','ASC')->limit(5)->get();
    
                return $this->response('success', ['data' => $users]);
            }else{
                return $this->response('noresult');
            }
            

        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function getsuperdistributor(Request $request){ 
        $userdata = Auth::user(); 
        if($userdata){
            $return =   array();
            $filter['usertype'] = 2; 
            
            if ($userdata['usertype'] == 3) {
                $filter['sd'] = $userdata->userid;
               // $query->orwhere($filter['sd']);
            }
            $search = $request->input('searchvalue');
            $users  =  $query = DB::table('users');
            $query->select('id','fullname','email','phone','username'); 
            $query->where(function ($query) use ($search) {
                $query->where('fullname', 'like',  trim($search) . '%')
                 ->orwhere('email', 'like', trim($search) . '%')
                 ->orwhere('phone', 'like',  trim($search) . '%') 
                 ->orwhere('username', 'like', trim($search) . '%');
                });  
            $query->where('status',1); 
            $query->orderBy('id','DESC');
            $query->limit(5);
            $totaldata = $query->get();
            return $this->response('success', ['data' => $totaldata]);
        }else{
            return $this->response('noresult');
        } 
    }
    public function getdistributor(Request $request){
        $userdata = Auth::user(); 
        dd($userdata);
        if($userdata){
            $return =   array();
            $filter['usertype'] = 1; 
            
            if ($userdata['usertype'] == 5) {
                $filter['dist'] = $userdata->userid;
            }
            $search = $request->input('searchvalue');
            $users  =  $query = DB::table('users');
            $query->select('id','fullname','email','phone','username'); 
            $query->where(function ($query) use ($search) {
                $query->where('fullname', 'like',  trim($search) . '%')
                 ->orwhere('email', 'like', trim($search) . '%')
                 ->orwhere('phone', 'like',  trim($search) . '%') 
                 ->orwhere('username', 'like', trim($search) . '%');
                });  
            $query->where('status',1);
            $query->where($filter['dist']);
            $query->orderBy('id','DESC');
            $query->limit(5);
            $totaldata = $query->get();
            return $this->response('success', ['data' => $totaldata]);
        }else{
            return $this->response('noresult');
        } 
    }
}
