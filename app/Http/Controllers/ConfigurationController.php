<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Http\Traits\CommonTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\CashTransaction;
use App\Http\Traits\RechargeTrait;
use App\Http\Traits\CommissionTrait;
use Carbon\Carbon;
class ConfigurationController extends Controller
{
    use CommonTrait,RechargeTrait,CommissionTrait;
    
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
        $this->mindate      =   date('Y-m-d', strtotime(date('Y-m-d')));
        $this->today      = Carbon::now()->toDateString();
    }

    public function front(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
                'bintohex' => 'required',
                'encrypt' => 'required',
                'ip' => 'required',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }


        } catch (\Throwable $th) {
            //throw $th;
        }
    }


    public function superdistributor(Request $request){  
        $query = DB::select(("SELECT sdid,ttype, 
        sum(if(refunded = 0,sdcomm,'')) as credit, 
        sum(if(refunded = 1,sdcomm,'')) as debit
        FROM `tbl_transaction_cashdeposit`
        where sdid is NOT null 
        and date_format(`dateadded`,'%Y-%m-%d') >= '".$this->mindate."' and  sdcomm > 0 GROUP BY sdid,ttype")); 
        $totaldata = $query;  
       //dd($totaldata);
        foreach($totaldata  as  $val){
            $commission  =  $val->credit;
            $tds = 0;
            $netcomm = $commission - $tds;
            $reqData    =   array(
                "id"		=> 	$val->sdid,
                "amount"	=>	$netcomm,
                "commission"=>	$commission,
                "tds"		=>	$tds,
                "addeddate"	=>	 $this->today, 
                "narration"	=>	"Commission of ".RechargeTrait::txn_type($val->ttype)." Rs. ".$netcomm." of Date : ".$this->mindate,    
            );   
            $result = CommissionTrait::supercomm($reqData); 
            
        }
        return $this->response('success', $result);

    }

    public function distributor(Request $request){  
        $query = DB::select(("SELECT did,ttype, 
        sum(if(refunded = 0,dcomm,'')) as credit, 
        sum(if(refunded = 1,dcomm,'')) as debit
        FROM `tbl_transaction_cashdeposit`
        where did is NOT null 
        and date_format(`dateadded`,'%Y-%m-%d') >= '".$this->mindate."' and  dcomm > 0 GROUP BY did,ttype")); 
        $totaldata = $query;  
        foreach($totaldata  as  $val){
            $commission  =  $val->credit; 
            $tds = 0;
            $netcomm = $commission - $tds;
            $reqData    =   array(
                "id"		=> 	$val->did,
                "amount"	=>	$netcomm,
                "commission"=>	$commission,
                "tds"		=>	$tds,
                "addeddate"	=>	 $this->today, 
                "narration"	=>	"Commission of ".RechargeTrait::txn_type($val->ttype)." Rs. ".$netcomm." of Date : ".$this->mindate,    
            );  
            $result = CommissionTrait::distributorcomm($reqData); 
           
        }
        return $this->response('success', $result);

    }

    
}
