<?php

namespace App\Http\Controllers\User\DIST;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Traits\CommonTrait;
use App\Http\Traits\HeaderTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Config;
use App\Models\UserPasswordDetails as UserPassword;
class DistController extends Controller
{
    use CommonTrait,HeaderTrait;
    public function __construct() {
        $this->status = ['0' => "Pending", '1' => "Active", '2' => "Deactive"];
    }

    
    public function create(Request $request){ 
        try {
            $validated = Validator::make($request->all(), [
                'name'   => 'required',
                'firmname'  => 'required',
                "email"     => 'required|email|min:8|max:50|unique:users',
                "phone"     => 'required|digits:10|unique:users',
                "role"      => 'required|numeric|exists:roles,id',
                "status"    => 'required',
                "pannumber"    => 'required',
                "city"    => 'required',
                "state"    => 'required',
                "pincode"    => 'required',
                "gender"    => 'required',
                "accounts"    => 'required',  
                 
            ]);
            $userdata = Auth::user();
          
            if(!in_array($userdata->role,array("3"))) { 
                $validated->sometimes('supdistributor', 'required', function ($input) {
                    return ($input->superdistributor_id === null) ;
                });
            }
            if ($validated->fails()) {
                $message   = $this->validationResponse($validated->errors());
                return $this->response('validatorerrors', $message);
            }
            
            if ($userdata) {
                if ($userdata->role == 1) {
                    $valid = array("pannumber" => $request->pannumber, "usertype" => 3);
                    if ($request->userid != "") {
                        $valid['id'] = $request->userid;
                    }  
                    $ignore = '';
                    $validpan = $this->validatepan($valid,$ignore); 
                  
                    if (!$validpan) {

                        $response = [
                            'errors' => "invalid!",
                            'message' => "Please Enter a Unique PAN Number"
                        ];
                        return $this->response('notvalid', $response); 
                    }
                   
                    if ($userdata->role == 3) { 
                        $requestdata["supdistributor"] = $userdata->id; 
                    } else {
                        $rs                            = $this->getaSd(array('id' => $request['supdistributor'])); 
                        $requestdata["supdistributor"] = $rs->id; 
                    }
 
                    $validmobile = $this->uniquesubmobile($request->phone);
                    if ($validmobile['status'] && $validmobile['count'] == 0) {   
                        $requestdata['fullname']   = strtoupper($request->name);
                        $requestdata['firmname']   = strtoupper($request->firmname);
                         $requestdata['email']      = strtoupper($request->email);
                         $requestdata['address']    = strtoupper($request->address);
                         $requestdata['pannumber']  = strtoupper($request->pannumber);
                         $requestdata['status']     = $request->status;
                         $requestdata['city']       = $request->city;
                         $requestdata['state']      = $request->state;
                         $requestdata['pincode']    = $request->pincode;
                         $requestdata['gender']     = $request->gender;
                         $requestdata['accounts']   = 20231002; 
                         $password                  = $this->generateRandomString(8);
                         $slot                      = $this->config(11);
                         $count                     = $slot + 1;
                         $username                  = env('PREFIX') . "D00" . $count;
                         $requestdata['username']   = $username; 
                        $requestdata['role']   = 4;
                        $requestdata['balance']    = 0;
                        $requestdata['credit']     = 0;
                        $requestdata['firstlogin'] = 0;
                        $requestdata['twostep']    = 1;
                        $requestdata['bysignup']   = 1;
                        $requestdata['minbalance'] = 0;
                        $requestdata['dob']        = date('Y-m-d', strtotime($request->dob));
                        $requestdata['remarks']    = "Created Distributor by User(" . $userdata->username . ")" . " on " . date("Y-m-d") . "";
                        $requestdata['phone']            = $request->phone;
                        $requestdata['allowfundrequest'] = 1;
                        $requestdata['gstnumber']        = $request->gstnumber; 

                        //dd($requestdata);
                        $SdAdd = User::insert($requestdata);  
                        $insertId =DB::getPdo()->lastInsertId();
                        if($SdAdd){
                            $result = Config::where("id","11")->first();
                            $count = $result->value+1;
                                Config::where('id',11)->update([
                                    'value' => $count 
                                ]);

                                $user_password              = new UserPassword;
                                $user_password->user_id     = $insertId;
                                $user_password->password    = Hash::make($password);
                                $user_password->expired_at  = date('Y-m-d', strtotime("+30 days"));
                                $user_password->status     = 1;
                                $user_password->save();
                            $array = array("[name]" => $requestdata['fullname'], "[username]" => $username, "[password]" => $password);
                            $response = [
                                'message' => "Account created. Your Username : " . $username . " & Password : " . $password 
                            ];
                            return $this->response('success', $response);
                        }else{
                            $response = [
                                'errors' => "invalid!",
                                'message' => "Something Went wrong please contact Customer Care!!"
                            ];
                            return $this->response('notvalid', $response);
                        }
                    }else {
                        $response = [
                            'errors' => "invalid!",
                            'message' => "Please provide a unique contact number"
                        ];
                        return $this->response('notvalid', $response); 
                    }
                }
            } 
            
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }
}