<?php
namespace App\Http\Controllers\BussinessBanking;
use App\Http\Controllers\Controller;
use App\Http\Traits\CommonTrait;
use App\Models\BankDetails;
use App\Models\BankForm;
use App\Models\AccountType;
use App\Models\BankDetailsData;
use App\Models\BankDetailsRemark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Traits\HeaderTrait;


class PartnerRequests extends Controller
{
    use CommonTrait, HeaderTrait;
    public function __construct() {
        $this->type   = "details";
        $this->status = ['0' => "Pending", '1' => "Approved", '2' => "Rejected"];
    }
    public function cibRegistrations(Request $request)
    {
        try{
            $startdate = $request->startdate;
            $enddate = $request->enddate;
            $search = $request->searchvalue;
            $orderby= $request->orderby;
            $order  = $request->order;
            $startdate = !empty($startdate)?date('Y-m-d',strtotime($startdate)):date('Y-m-d');
            $enddate = !empty($enddate)?date('Y-m-d',strtotime($enddate)):date('Y-m-d');
            
            $searchColumn = ['bank_details.id','bank_lists.name','bank_details.holderName','bank_details.account_number'];
            $select = ['bank_details.id as cib_id','bank_lists.name as bank_name','bank_details.holderName','bank_details.account_number','bank_details.ifsc as ifsc','account_types.type as account_type','bank_details.bankuserid','bank_details.bankloginid','bank_details.corporateid','bank_details.corporate_name','bank_details.status as status','bank_details.created_at as created', 'users.fullname as partner'];
            $query = BankDetails::select($select)->join('users', 'users.id','=','bank_details.user_id')->join('bank_lists', 'bank_lists.id','=','bank_details.bank_id')->join('account_types', 'account_types.id','=','bank_details.account_type');
            $query->whereDate('bank_details.created_at', '>=', $startdate);
            $query->whereDate('bank_details.created_at', '<=', $enddate);
            $totalCount = $query->count();
            if(!empty($search)){
                $query->where(function($query) use ($searchColumn, $search){
                    foreach($searchColumn as $column){
                        $query->orwhere($column, 'like', '%' .  trim($search) . '%');
                    }
                });                
            }

            (!empty($orderby) && !empty($order))? $query->orderBy('bank_details.'.$orderby, $order): $query->orderBy("bank_details.id", "desc");
            $length = (!empty($request->length))? $request->length: 20;
            $start  = (!empty($request->start))? $request->start: 0;
            $list   = $query->skip($start)->take($length)->get();
            foreach($list as $key => $val){
                $list[$key]->status = $this->status[$val->status];
                $list[$key]->created = date('d-m-Y',strtotime($val->created_at));
            }
            $count  = count($list);
            $header = $this->cib();
            $details = [
                "message" => "List fetched",
                "recordsFiltered" => $count,
                "recordsTotal" => $totalCount,
                "header" => $header,
                "data" => $list
            ];
            return $this->response('success', $details);

        }catch(\Throwable $th){
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function getCib(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'cib_id' => 'required',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            if($request->has('type') && $request->type != ""){
                $this->type = $request->type;
            }
            $cib_id = $request->cib_id;
            $bankAccount = BankDetails::where('id',$request->cib_id)->with('bank_id:id,name')->with('remarks', function ($query) use($cib_id) {
                $query->where('field_id',0);
                $query->orderBy('created_at','DESC');
                $query->select('bank_details_id','remark');
            })->first();
            
            if(!$bankAccount){
                return $this->response('notvalid', ['message' => "Account Not found"]);
            }
            $bankAccount = $bankAccount->toArray();

            $formData = BankForm::select('id','label','fieldname','type','required','placeholder')->where('status',1)->where('form_type',$this->type)->with('values', function ($query) use($bankAccount) {
                $query->where('bank_details_id','=',$bankAccount['id']);
                $query->select('id as document_id','field_id', 'value','status');
            })->with('options')->with('remarks', function ($query) use($bankAccount) {
                $query->where('bank_details_id','=',$bankAccount['id']);
                $query->orderBy('created_at','DESC');
                $query->select('field_id','remark');
            })->where('bank_id',$bankAccount['bank_id'])->orderBy('index','ASC')->get()->toArray();
            
           // dd($formData);
            
            $details = [
                'cib_id' => $bankAccount['id'],
                'account_type' =>  AccountType::where('id',$bankAccount['account_type'])->pluck('type')->first() ,
                'status' => $bankAccount['status'],
                'bank' => $bankAccount['bank_id']['name'],
                'account_information' => $bankAccount,
                'form' => $formData,
            ];
            return $this->response('success', ['message' => $details]);

        }catch(\Throwable $th){
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function updateCib(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'cib_id' => 'required',
                'status' => 'required|numeric|in:1,2',
                'prefix' => 'required_if:status,==,1',
                'sender_name' => 'required_if:status,==,1',
                'sender_email' => 'required_if:status,==,1',
                'sender_phone' => 'required_if:status,==,1',
                'profile_id' => 'required_if:status,==,1',
                'vpa_id' => 'required_if:status,==,1',
                'bc_id' => 'required_if:status,==,1',
                'passcode' => 'required_if:status,==,1',
                'remarks' => 'required_if:status,==,2',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $bankAccount = BankDetails::find($request->cib_id);
            
            if(!$bankAccount){
                return $this->response('notvalid', ['message' => "Account Not found"]);
            }

            $bankAccount->status = $request->status;
            $bankAccount->prefix = $request->prefix;
            $bankAccount->sender_name = $request->sender_name;
            $bankAccount->sender_email = $request->sender_email;
            $bankAccount->sender_phone = $request->sender_phone;
            $bankAccount->profile_id = $request->profile_id;
            $bankAccount->vpa_id = $request->vpa_id;
            $bankAccount->bc_id = $request->bc_id;
            $bankAccount->passcode = $request->passcode;
            
            if($request->has('remarks')){
                $data = [
                    'field_id' => 0,
                    'bank_details_id' => $request->cib_id,
                    'remark' => $request->remarks,
                ];
                BankDetailsRemark::insert($data);
            }
            $bankAccount->update();
            return $this->response('success', ['message' => "Updated Successfully"]);

        }catch(\Throwable $th){
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
        
    }


    public function updateDocument(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'cib_id' => 'required',
                'field_id' => 'required',
                'remark' => 'required',
                'status' => 'required|in:1,2'
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $bankAccount = BankDetails::find($request->cib_id);
            
            if(!$bankAccount){
                return $this->response('notvalid', ['message' => "Account Not found"]);
            }

            $BankDetailsData = BankDetailsData::where('bank_details_id',$request->cib_id)->where('field_id',$request->field_id)->first();
            
            if(!$BankDetailsData){
                return $this->response('notvalid', ['message' => "Document not refer to the account"]);
            }

            $BankDetailsData->status = $request->status;
            $BankDetailsData->update();
            $data = [
                'field_id' => $request->field_id,
                'bank_details_id' => $request->cib_id,
                'remark' => $request->remark,
            ];
            BankDetailsRemark::insert($data);

            return $this->response('success', ['message' => "Updated Successfully"]);

        }catch(\Throwable $th){
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }
}