<?php

namespace App\Http\Controllers\Permission;
use App\Models\User;
use App\Models\Permission;
use App\Http\Controllers\Controller;
use App\Http\Traits\CommonTrait;   
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Traits\HeaderTrait;
use Illuminate\Support\Facades\Auth;
class PermissionController extends Controller
{
    use CommonTrait,HeaderTrait;
    public function __construct() { 
        $this->status 	= ['Inactive','Active'];
        $this->ctype 	= ['Percentage','Fixed'];
    }

    public function Permissionlist(Request $request){
        $userdata = Auth::user();   
        if(in_array($userdata->role,[1,2])){
            try { 
                $validated = Validator::make($request->all(), [
                    'id'   => 'required',
                ]);
                if ($validated->fails()) {
                    $message   = $this->validationResponse($validated->errors());
                    return $this->response('validatorerrors', $message);
                }
                $records = Permission::select("*")->where("userid",$request->id)->first(); 
                if($records){
                    unset($records->pg);
                    unset($records->default_biometric);
                    $permission = $records;

                    $response = [
                        'message' => "Data Found",
                        'userid' =>$records->userid,
                        'username' =>$records->merchant_id,
                        'name'=>$records->name,
                        'data'    =>$permission
                    ]; 
                    unset($response['userid']);
                    //unset($response['username']);
                    unset($response['name']);
                    return $this->response('success', $response);
                }else{
                    $response = [
                        'errors' => "invalid!",
                        'message' => "No data Found!"
                    ];
                    return $this->response('notvalid', $response); 
                }
                
                 
            } catch (\Exception $e) {
                return  $this->response('internalservererror', ['message' => $e->getMessage()]);
            }
        }else{

        }
    }
    public function Permissionupdate(Request $request){
        $userdata = Auth::user();   
        if(in_array($userdata->role,[1,2])){
            try { 
                $validated = Validator::make($request->all(), [
                    'id'   => 'required',
                    'permission' => 'required'
                ]);
                if ($validated->fails()) {
                    $message   = $this->validationResponse($validated->errors());
                    return $this->response('validatorerrors', $message);
                }
                $permission = json_decode($request->permission, true);
                $permissionupdate = Permission::where("userid", $request->id)
                    ->update(
                        [
                        "funding" => $permission['funding'],
                        "dmt" => $permission['dmt'],
                        "bill" => $permission['bill'],
                        "recharge" => $permission['recharge'],
                        "aeps" => $permission['aeps'],
                        "pan" => $permission['pan'],
                        "pg" => $permission['pg'],
                        "payout" => $permission['payout'],
                        "wallet_load" => $permission['wallet_load']
                        ]
                    );  
                if($permissionupdate){  

                    $response = [
                        'message' => "Permission updated successfully"
                    ];  
                    return $this->response('success', $response);
                }else{
                    $response = [
                        'errors' => "invalid!",
                        'message' => "Unable to update the permissions"
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