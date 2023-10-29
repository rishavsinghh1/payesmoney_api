<?php
namespace App\Http\Traits;

trait HeaderTrait
{
    static function bankslist(){
        $result = array();
        $result[] = ['name' => 'name', 'value' => 'Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'logo', 'value' => 'Logo', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'status', 'value' => 'Status', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'created', 'value' => 'Created At', 'is_show' => 1, 'issort' => 0];
        return $result;
    }

    static function accountTypeHeader(){
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'type', 'value' => 'Type', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'status', 'value' => 'Status', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'created', 'value' => 'Created At', 'is_show' => 1, 'issort' => 0];
        return $result;
    }

    static function bankform(){
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'label', 'value' => 'Label', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'bank_name', 'value' => 'Bank', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'type', 'value' => 'Type', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'required', 'value' => 'Required', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'status', 'value' => 'Status', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'created', 'value' => 'Created At', 'is_show' => 1, 'issort' => 0];
        return $result;
    }
    static function bankformnew(){
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'zip', 'value' => 'ZIP', 'is_show' => 1, 'issort' => 0];
       return $result;
    }

    static function modules(){
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'name', 'value' => 'Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'status', 'value' => 'Status', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'created', 'value' => 'Created At', 'is_show' => 1, 'issort' => 0];
        return $result;
    }

    static function roles(){
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'name', 'value' => 'Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'status', 'value' => 'Status', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'created', 'value' => 'Created At', 'is_show' => 1, 'issort' => 0];
        return $result;
    }

    static function menuItems()
    {
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'name', 'value' => 'Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'type', 'value' => 'Type', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'urlapi', 'value' => 'URL API', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'icon', 'value' => 'Icon', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'menu', 'value' => 'Menu', 'is_show' => 1, 'issort' => 0];
        return $result;
    }

    static function notifications()
    {
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'title', 'value' => 'Title', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'start_date', 'value' => 'Start Date', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'end_date', 'value' => 'End Date', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'created', 'value' => 'Created At', 'is_show' => 1, 'issort' => 0];
        return $result;
    }

    static function users(){
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'fullname', 'value' => 'Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'username', 'value' => 'Username', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'email', 'value' => 'Email', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'phone', 'value' => 'Phone', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'role', 'value' => 'Role', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'status', 'value' => 'Status', 'is_show' => 1, 'issort' => 0];
        return $result;
    }

    static function cib(){
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'partner', 'value' => 'Partner Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'holderName', 'value' => 'Acc. Holder', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'account_number', 'value' => 'Acc. no', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'ifsc', 'value' => 'IFSC', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'account_type', 'value' => 'Services', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'bankuserid', 'value' => 'Bank U.ID', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'bankloginid', 'value' => 'Bank L.ID', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'corporateid', 'value' => 'Bank C.ID', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'corporate_name', 'value' => 'Corporate Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'created', 'value' => 'Created At', 'is_show' => 1, 'issort' => 0];
         return $result;
    }
    
    static function transactionshead()
    {
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'merchant', 'value' => 'Merchant', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'bank', 'value' => 'Bank', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'txnid', 'value' => 'Txn. ID', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'refid', 'value' => 'Ref. ID', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'amount', 'value' => 'Amount', 'is_show' => $isshow, 'issort' => 0];
        $result[] = ['name' => 'status', 'value' => 'Status', 'is_show' => $isshow, 'issort' => 0];
        $result[] = ['name' => 'charges', 'value' => 'Charges', 'is_show' => $isshow, 'issort' => 0];
        $result[] = ['name' => 'gst', 'value' => 'gst', 'is_show' => $isshow, 'issort' => 0];
        $result[] = ['name' => 'addeddate', 'value' => 'Added Date', 'is_show' => $isshow, 'issort' => 0];
        $result[] = ['name' => 'vpa', 'value' => 'VPA', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'original_bank_rrn', 'value' => 'RRN', 'is_show' => $isshow, 'issort' => 0];
        $result[] = ['name' => 'payer_name', 'value' => 'Payer Name', 'is_show' => $isshow, 'issort' => 0];
        $result[] = ['name' => 'payer_va', 'value' => 'Payer VA', 'is_show' => $isshow, 'issort' => 0];
        $result[] = ['name' => 'qr_type', 'value' => 'QR Type', 'is_show' => $isshow, 'issort' => 0];
        $result[] = ['name' => 'txn_completion_date', 'value' => 'TXN Completion Date', 'is_show' => $isshow, 'issort' => 0];
        $result[] = ['name' => 'txn_init_date', 'value' => 'TXN Init. Date', 'is_show' => $isshow, 'issort' => 0];return $result;
    }

    static function vpastatement()
    {
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'partner', 'value' => 'Partner', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'customer_name', 'value' => 'Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'pan', 'value' => 'Pan', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'mobile', 'value' => 'Mobile', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'bank', 'value' => 'Bank', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'vpa', 'value' => 'VPA', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'merchantID', 'value' => 'Merchant Code', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'status', 'value' => 'Status', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'created', 'value' => 'Create Time', 'is_show' => 1, 'issort' => 0];
        return $result;
    }

    static function beneficiarylist(){
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'partner', 'value' => 'Partner Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'name', 'value' => 'Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'cpname', 'value' => 'Contact Parson Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'mobile', 'value' => 'Mobile', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'email', 'value' => 'Email', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'accno', 'value' => 'Account Number', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'ifsccode', 'value' => 'IFSC Code', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'bankname', 'value' => 'Bank Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'type', 'value' => 'Customer Type', 'is_show' => 1, 'issort' => 0];
        // $result[] = ['name' => 'status', 'value' => 'Status', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'remarks', 'value' => 'Remarks', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'createdat', 'value' => 'Create Time', 'is_show' => 1, 'issort' => 0];
        return $result;
    }

    static function payoutlist(){
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'fullname', 'value' => 'Partner', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'bankname', 'value' => 'Bank Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'bene_acc_no', 'value' => 'Beneficiary A/c', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'bene_acc_ifsc', 'value' => 'IFSC Code', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'amount', 'value' => 'Amount', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'bank_urn', 'value' => 'Urn', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'utr_rrn', 'value' => 'Utr/rrn', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'mode', 'value' => 'Mode', 'is_show' => 1, 'issort' => 0];
        // $result[] = ['name' => 'status', 'value' => 'Status', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'remarks', 'value' => 'Remarks', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'createdat', 'value' => 'Create Time', 'is_show' => 1, 'issort' => 0];
        return $result;
    }

    static function vastatement()
    {
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'partner', 'value' => 'Partner', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'bank', 'value' => 'Bank', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'acc_no', 'value' => 'Account No.', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'name', 'value' => 'Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'email', 'value' => 'Email', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'phone', 'value' => 'Phone', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'pan', 'value' => 'Pan', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'status', 'value' => 'Status', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'created_at', 'value' => 'Created at', 'is_show' => 1, 'issort' => 0];
        return $result;
    }

    static function vatransactionshead()
    {
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'merchant', 'value' => 'Merchant', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'bank', 'value' => 'Bank', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'created_at', 'value' => 'Create Time', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'account_name', 'value' => 'Account Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'acc_no', 'value' => 'Account No.', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'amount', 'value' => 'Amount', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'p_mode', 'value' => 'Payment Mode', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'remitter_name', 'value' => 'Remitter Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'remitter_ac_no', 'value' => 'Remitter Acc. no.', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'utr', 'value' => 'UTR', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'txn_date', 'value' => 'Transaction Date', 'is_show' => 1, 'issort' => 0];
        return $result;
    }


    static function partners()
    {
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'partner_name', 'value' => 'Partner Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'user_id', 'value' => 'User ID', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'phone', 'value' => 'Phone', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'email', 'value' => 'Email', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'status', 'value' => 'Status', 'is_show' => 1, 'issort' => 0];
        return $result;
    }

    static function txn_adminfund_header(){
        $isshow =1; 
        $result[] = ['name'=>'reqid','value'=>'REQ. ID','is_show'=>$isshow,'issort'=>0];
        $result[] = ['name'=>'name','value'=>'Bank Name','is_show'=>$isshow,'issort'=>0];
        $result[] = ['name'=>'username','value'=>'USERNAME','is_show'=>$isshow,'issort'=>0]; 
        $result[] = ['name'=>'current_balance','value'=>'CURRENT BALANCE','is_show'=>$isshow,'issort'=>0];
        $result[] = ['name'=>'amount','value'=>'REQ AMOUNT','is_show'=>$isshow,'issort'=>0];
        $result[] = ['name'=>'depositeddate','value'=>'DEPOSITE DATE','is_show'=>$isshow,'issort'=>0]; 
        $result[] = ['name'=>'referencenumber','value'=>'REF NO.','is_show'=>$isshow,'issort'=>0]; 
        $result[] = ['name'=>'status','value'=>'STATUS','is_show'=>$isshow,'issort'=>0]; 
        $result[] = ['name'=>'phone','value'=>'PHONE NO.','is_show'=>$isshow,'issort'=>0]; 
        $result[] = ['name'=>'requestremark','value'=>'REMARKS','is_show'=>$isshow,'issort'=>0]; 
        return $result;
    }


    static function txn_ADMIN_header()
    {
        $isshow = 1;
        $issort = 1;
        $column[] = ['name' => 'txnid', 'value' => 'TXN ID', 'is_show' => $isshow, 'issort' => $issort]; 
        $column[] = ['name' => 'addeddate', 'value' => 'DATE TIME', 'is_show' => $isshow, 'issort' => $issort]; 
        $column[] = ['name' => 'username', 'value' => 'USERID', 'is_show' => $isshow, 'issort' => $issort];
        $column[] = ['name' => 'firmname', 'value' => 'FIRM', 'is_show' => $isshow, 'issort' => $issort];
        $column[] = ['name' => 'operatorname', 'value' => 'OPERATOR', 'is_show' => $isshow, 'issort' => $issort]; 
        $column[] = ['name' => 'canumber', 'value' => 'DEVICE', 'is_show' => $isshow, 'issort' => 0];
        $column[] = ['name' => 'amount', 'value' => 'AMOUNT', 'is_show' => $isshow, 'issort' => 0];  
        $column[] = ['name' => 'status', 'value' => 'STATUS', 'is_show' => $isshow, 'issort' => 0];
        $column[] = ['name' => 'operatorid', 'value' => 'OPID', 'is_show' => $isshow, 'issort' => 0]; 
        $column[] = ['name' => 'diff', 'value' => 'DIFF', 'is_show' => $isshow, 'issort' => 0]; 
        $column[] = ['name' => 'refundtxnid', 'value' => 'REFUND ID', 'is_show' => $isshow, 'issort' => 0];  
        $column[] = ['name' => 'opening', 'value' => 'OPENING', 'is_show' => $isshow, 'issort' => 0];
        $column[] = ['name' => 'debit', 'value' => 'DEBIT', 'is_show' => $isshow, 'issort' => 0]; 
        $column[] = ['name' => 'credit', 'value' => 'CREDIT', 'is_show' => $isshow, 'issort' => 0];
        $column[] = ['name' => 'closing', 'value' => 'CLOSING', 'is_show' => $isshow, 'issort' => 0]; 
        $column[] = ['name' => 'sdcomm', 'value' => 'SD COMM', 'is_show' => $isshow, 'issort' => 0]; 
        $column[] = ['name' => 'dcomm', 'value' => 'DIST COMM', 'is_show' => $isshow, 'issort' => 0];    
        $column[] = ['name' => 'comm', 'value' => 'RT COMM', 'is_show' => $isshow, 'issort' => 0];  
        $column[] = ['name' => 'orderid', 'value' => 'ORDER ID', 'is_show' => $isshow, 'issort' => 0];  
        return $column;
    } 
    static function txn_SD_header()
    {
        $isshow = 1;
        $issort = 1;
        $column[] = ['name' => 'txnid', 'value' => 'TXN ID', 'is_show' => $isshow, 'issort' => $issort]; 
        $column[] = ['name' => 'addeddate', 'value' => 'DATE TIME', 'is_show' => $isshow, 'issort' => $issort]; 
        $column[] = ['name' => 'username', 'value' => 'USERID', 'is_show' => $isshow, 'issort' => $issort];
        $column[] = ['name' => 'firmname', 'value' => 'FIRM', 'is_show' => $isshow, 'issort' => $issort];
        $column[] = ['name' => 'operatorname', 'value' => 'OPERATOR', 'is_show' => $isshow, 'issort' => $issort]; 
        $column[] = ['name' => 'canumber', 'value' => 'DEVICE', 'is_show' => $isshow, 'issort' => 0];
        $column[] = ['name' => 'amount', 'value' => 'AMOUNT', 'is_show' => $isshow, 'issort' => 0];  
        $column[] = ['name' => 'status', 'value' => 'STATUS', 'is_show' => $isshow, 'issort' => 0];
        $column[] = ['name' => 'operatorid', 'value' => 'OPID', 'is_show' => $isshow, 'issort' => 0]; 
        $column[] = ['name' => 'diff', 'value' => 'DIFF', 'is_show' => $isshow, 'issort' => 0]; 
        $column[] = ['name' => 'refundtxnid', 'value' => 'REFUND ID', 'is_show' => $isshow, 'issort' => 0];  
        $column[] = ['name' => 'opening', 'value' => 'OPENING', 'is_show' => $isshow, 'issort' => 0];
        $column[] = ['name' => 'debit', 'value' => 'DEBIT', 'is_show' => $isshow, 'issort' => 0]; 
        $column[] = ['name' => 'credit', 'value' => 'CREDIT', 'is_show' => $isshow, 'issort' => 0];
        $column[] = ['name' => 'closing', 'value' => 'CLOSING', 'is_show' => $isshow, 'issort' => 0]; 
        $column[] = ['name' => 'sdcomm', 'value' => 'SD COMM', 'is_show' => $isshow, 'issort' => 0]; 
        $column[] = ['name' => 'dcomm', 'value' => 'DIST COMM', 'is_show' => $isshow, 'issort' => 0];    
        $column[] = ['name' => 'comm', 'value' => 'RT COMM', 'is_show' => $isshow, 'issort' => 0];  
        $column[] = ['name' => 'orderid', 'value' => 'ORDER ID', 'is_show' => $isshow, 'issort' => 0];  
        return $column;
    } 
    static function txn_DIST_header()
    {
        $isshow = 1;
        $issort = 1;
        $column[] = ['name' => 'txnid', 'value' => 'TXN ID', 'is_show' => $isshow, 'issort' => $issort]; 
        $column[] = ['name' => 'addeddate', 'value' => 'DATE TIME', 'is_show' => $isshow, 'issort' => $issort]; 
        $column[] = ['name' => 'username', 'value' => 'USERID', 'is_show' => $isshow, 'issort' => $issort];
        $column[] = ['name' => 'firmname', 'value' => 'FIRM', 'is_show' => $isshow, 'issort' => $issort];
        $column[] = ['name' => 'operatorname', 'value' => 'OPERATOR', 'is_show' => $isshow, 'issort' => $issort]; 
        $column[] = ['name' => 'canumber', 'value' => 'DEVICE', 'is_show' => $isshow, 'issort' => 0];
        $column[] = ['name' => 'amount', 'value' => 'AMOUNT', 'is_show' => $isshow, 'issort' => 0];  
        $column[] = ['name' => 'status', 'value' => 'STATUS', 'is_show' => $isshow, 'issort' => 0];
        $column[] = ['name' => 'operatorid', 'value' => 'OPID', 'is_show' => $isshow, 'issort' => 0]; 
        $column[] = ['name' => 'diff', 'value' => 'DIFF', 'is_show' => $isshow, 'issort' => 0]; 
        $column[] = ['name' => 'refundtxnid', 'value' => 'REFUND ID', 'is_show' => $isshow, 'issort' => 0];  
        $column[] = ['name' => 'opening', 'value' => 'OPENING', 'is_show' => $isshow, 'issort' => 0];
        $column[] = ['name' => 'debit', 'value' => 'DEBIT', 'is_show' => $isshow, 'issort' => 0]; 
        $column[] = ['name' => 'credit', 'value' => 'CREDIT', 'is_show' => $isshow, 'issort' => 0];
        $column[] = ['name' => 'closing', 'value' => 'CLOSING', 'is_show' => $isshow, 'issort' => 0];  
        $column[] = ['name' => 'dcomm', 'value' => 'DIST COMM', 'is_show' => $isshow, 'issort' => 0];    
        $column[] = ['name' => 'comm', 'value' => 'RT COMM', 'is_show' => $isshow, 'issort' => 0];  
        $column[] = ['name' => 'orderid', 'value' => 'ORDER ID', 'is_show' => $isshow, 'issort' => 0];  
        return $column;
    } 

    static function txn_REATILER_header()
    {
        $isshow = 1;
        $issort = 1;
        $column[] = ['name' => 'txnid', 'value' => 'TXN ID', 'is_show' => $isshow, 'issort' => $issort]; 
        $column[] = ['name' => 'addeddate', 'value' => 'DATE TIME', 'is_show' => $isshow, 'issort' => $issort]; 
        $column[] = ['name' => 'username', 'value' => 'USERID', 'is_show' => $isshow, 'issort' => $issort];
        $column[] = ['name' => 'firmname', 'value' => 'FIRM', 'is_show' => $isshow, 'issort' => $issort];
        $column[] = ['name' => 'operatorname', 'value' => 'OPERATOR', 'is_show' => $isshow, 'issort' => $issort]; 
        $column[] = ['name' => 'canumber', 'value' => 'DEVICE', 'is_show' => $isshow, 'issort' => 0];
        $column[] = ['name' => 'amount', 'value' => 'AMOUNT', 'is_show' => $isshow, 'issort' => 0];  
        $column[] = ['name' => 'status', 'value' => 'STATUS', 'is_show' => $isshow, 'issort' => 0];
        $column[] = ['name' => 'operatorid', 'value' => 'OPID', 'is_show' => $isshow, 'issort' => 0]; 
        $column[] = ['name' => 'diff', 'value' => 'DIFF', 'is_show' => $isshow, 'issort' => 0]; 
        $column[] = ['name' => 'refundtxnid', 'value' => 'REFUND ID', 'is_show' => $isshow, 'issort' => 0];  
        $column[] = ['name' => 'opening', 'value' => 'OPENING', 'is_show' => $isshow, 'issort' => 0];
        $column[] = ['name' => 'debit', 'value' => 'DEBIT', 'is_show' => $isshow, 'issort' => 0]; 
        $column[] = ['name' => 'credit', 'value' => 'CREDIT', 'is_show' => $isshow, 'issort' => 0];
        $column[] = ['name' => 'closing', 'value' => 'CLOSING', 'is_show' => $isshow, 'issort' => 0];    
        $column[] = ['name' => 'comm', 'value' => 'RT COMM', 'is_show' => $isshow, 'issort' => 0];  
        $column[] = ['name' => 'orderid', 'value' => 'ORDER ID', 'is_show' => $isshow, 'issort' => 0];  
        return $column;
    } 
    static function SdHeader()
    {
         $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'firmname', 'value' => 'FIRMNAME', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'username', 'value' => 'USERNAME', 'is_show' => 1, 'issort' => 0]; 
        $result[] = ['name' => 'phone', 'value' => 'PHONE', 'is_show' => 1, 'issort' => 0]; 
        $result[] = ['name' => 'cd_balance', 'value' => 'BALANCE', 'is_show' => 1, 'issort' => 0]; 
        // $result[] = ['name' => 'status', 'value' => 'STATUS', 'is_show' => 1, 'issort' => 0];
        // $result[] = ['name' => 'is_kyc', 'value' => 'KYC STATUS', 'is_show' => 1, 'issort' => 0];  
        $result[] = ['name' => 'created_at', 'value' => 'ADDEDDATE', 'is_show' => 1, 'issort' => 0]; 
        return $result;
    }
    static function RAdminHeader()
    {
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'firmname', 'value' => 'FIRMNAME', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'fullname', 'value' => 'NAME', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'username', 'value' => 'USERNAME', 'is_show' => 1, 'issort' => 0]; 
        $result[] = ['name' => 'phone', 'value' => 'PHONE', 'is_show' => 1, 'issort' => 0]; 
        $result[] = ['name' => 'cd_balance', 'value' => 'BALANCE', 'is_show' => 1, 'issort' => 0]; 
        
        // $result[] = ['name' => 'status', 'value' => 'STATUS', 'is_show' => 1, 'issort' => 0];
        // $result[] = ['name' => 'is_kyc', 'value' => 'KYC STATUS', 'is_show' => 1, 'issort' => 0];  
        $result[] = ['name' => 'created_at', 'value' => 'ADDEDDATE', 'is_show' => 1, 'issort' => 0]; 
        return $result;
    }
    static function RRTHeader()
    {
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'firmname', 'value' => 'FIRMNAME', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'username', 'value' => 'USERNAME', 'is_show' => 1, 'issort' => 0]; 
        $result[] = ['name' => 'phone', 'value' => 'PHONE', 'is_show' => 1, 'issort' => 0]; 
        $result[] = ['name' => 'cd_balance', 'value' => 'BALANCE', 'is_show' => 1, 'issort' => 0]; 
        // $result[] = ['name' => 'status', 'value' => 'STATUS', 'is_show' => 1, 'issort' => 0];
        // $result[] = ['name' => 'is_kyc', 'value' => 'KYC STATUS', 'is_show' => 1, 'issort' => 0];  
        $result[] = ['name' => 'created_at', 'value' => 'ADDEDDATE', 'is_show' => 1, 'issort' => 0]; 
        return $result;
    }
    static function distHeader()
    {
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'firmname', 'value' => 'FIRMNAME', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'username', 'value' => 'USERNAME', 'is_show' => 1, 'issort' => 0]; 
        $result[] = ['name' => 'phone', 'value' => 'PHONE', 'is_show' => 1, 'issort' => 0]; 
        $result[] = ['name' => 'cd_balance', 'value' => 'BALANCE', 'is_show' => 1, 'issort' => 0]; 
        // $result[] = ['name' => 'status', 'value' => 'STATUS', 'is_show' => 1, 'issort' => 0];
        // $result[] = ['name' => 'is_kyc', 'value' => 'KYC STATUS', 'is_show' => 1, 'issort' => 0];  
        $result[] = ['name' => 'created_at', 'value' => 'ADDEDDATE', 'is_show' => 1, 'issort' => 0]; 
        return $result;
    }
    
     
    static function getrequest_Fund(){
        $isshow =1; 
        $result[] = ['name'=>'txnid','value'=>'Txn. ID','is_show'=>$isshow,'issort'=>0];
        $result[] = ['name'=>'bankname','value'=>'Bank Name','is_show'=>$isshow,'issort'=>0];
        $result[] = ['name'=>'image','value'=>'image','is_show'=>$isshow,'issort'=>0]; 
        $result[] = ['name'=>'depositeddate','value'=>'DEPOSITE DATE','is_show'=>$isshow,'issort'=>0];
        $result[] = ['name'=>'amount','value'=>'AMOUNT','is_show'=>$isshow,'issort'=>0];
        $result[] = ['name'=>'addeddate','value'=>'Created At','is_show'=>$isshow,'issort'=>0]; 
        $result[] = ['name'=>'acomment','value'=>'REMARKS','is_show'=>$isshow,'issort'=>0]; 
        return $result;
    }
    static function txn_ledger_admin_header()
    {
        $isshow = 1;
        $issort = 0; 
        
        $column[] = ['name' => 'txnid', 'value' => 'Transaction ID', 'is_show' => $isshow, 'issort' => $issort];
        $column[] = ['name' => 'dateadded', 'value' => 'Date Time', 'is_show' => $isshow, 'issort' => 0];
        $column[] = ['name' => 'narration', 'value' => 'Description', 'is_show' => $isshow, 'issort' => 0];
        $column[] = ['name' => 'username', 'value' => 'User', 'is_show' => $isshow, 'issort' => $issort];
        $column[] = ['name' => 'cd_opening', 'value' => 'Old', 'is_show' => $isshow, 'issort' => 0];
        $column[] = ['name' => 'debit', 'value' => 'Debit', 'is_show' => $isshow, 'issort' => 0]; 
        $column[] = ['name' => 'credit', 'value' => 'Credit', 'is_show' => $isshow, 'issort' => 0]; 
        $column[] = ['name' => 'cd_closing', 'value' => 'Current', 'is_show' => $isshow, 'issort' => 0];
        $column[] = ['name' => 'stype', 'value' => 'DEBIT ADMIN', 'is_show' => $isshow, 'issort' => 0];  
        $column[] = ['name' => 'remarks', 'value' => 'Remarks', 'is_show' => $isshow, 'issort' => 0]; 
        return $column;
     }  

     static function txn_S_Daybook_header()
     {
         $isshow = 1;
         $issort = 0;  
         $result[] = ['name'=>'opname','value'=>'OPNAME','is_show'=>$isshow,'issort'=>0];
         $result[] = ['name'=>'totalcount','value'=>'COUNT','is_show'=>$isshow,'issort'=>0]; 
         $result[] = ['name'=>'totalamt','value'=>'TOTAL AMOUNT','is_show'=>$isshow,'issort'=>0];
         $result[] = ['name'=>'totalsale','value'=>'SALE','is_show'=>$isshow,'issort'=>0];
         $result[] = ['name'=>'RTCOMM','value'=>'RT COMM','is_show'=>$isshow,'issort'=>0];
         $result[] = ['name'=>'DISTCOMM','value'=>'DIST COMM','is_show'=>$isshow,'issort'=>0];
         $result[] = ['name'=>'SDCOMM','value'=>'SD COMM','is_show'=>$isshow,'issort'=>0];
         $result[] = ['name'=>'totalcomm','value'=>'Total COMM','is_show'=>$isshow,'issort'=>0];
         $result[] = ['name'=>'salemcomm','value'=>'SALE(-)COMMISSION','is_show'=>$isshow,'issort'=>0];    
         return $result;
      } 
      static function txn_RT_Daybook_header()
     {
         $isshow = 1;
         $issort = 0;  
         $result[] = ['name'=>'opname','value'=>'OPNAME','is_show'=>$isshow,'issort'=>0];
         $result[] = ['name'=>'totalcount','value'=>'COUNT','is_show'=>$isshow,'issort'=>0]; 
         $result[] = ['name'=>'totalamt','value'=>'TOTAL AMOUNT','is_show'=>$isshow,'issort'=>0];
         $result[] = ['name'=>'totalsale','value'=>'SALE','is_show'=>$isshow,'issort'=>0];
         $result[] = ['name'=>'RTCOMM','value'=>'RT COMM','is_show'=>$isshow,'issort'=>0];  
         $result[] = ['name'=>'salemcomm','value'=>'SALE(-)COMMISSION','is_show'=>$isshow,'issort'=>0];    
         return $result;
      } 
      static function txn_SD_Daybook_header()
     {
         $isshow = 1;
         $issort = 0;  
         $result[] = ['name'=>'opname','value'=>'OPNAME','is_show'=>$isshow,'issort'=>0];
         $result[] = ['name'=>'totalcount','value'=>'COUNT','is_show'=>$isshow,'issort'=>0]; 
         $result[] = ['name'=>'totalamt','value'=>'TOTAL AMOUNT','is_show'=>$isshow,'issort'=>0];
         $result[] = ['name'=>'totalsale','value'=>'SALE','is_show'=>$isshow,'issort'=>0];
         $result[] = ['name'=>'RTCOMM','value'=>'RT COMM','is_show'=>$isshow,'issort'=>0];
         $result[] = ['name'=>'DISTCOMM','value'=>'DIST COMM','is_show'=>$isshow,'issort'=>0];
         $result[] = ['name'=>'SDCOMM','value'=>'SD COMM','is_show'=>$isshow,'issort'=>0];
         $result[] = ['name'=>'totalcomm','value'=>'Total COMM','is_show'=>$isshow,'issort'=>0];
         $result[] = ['name'=>'salemcomm','value'=>'SALE(-)COMMISSION','is_show'=>$isshow,'issort'=>0];    
         return $result;
      } 
      static function txn_DIST_Daybook_header()
     {
         $isshow = 1;
         $issort = 0;  
         $result[] = ['name'=>'opname','value'=>'OPNAME','is_show'=>$isshow,'issort'=>0];
         $result[] = ['name'=>'totalcount','value'=>'COUNT','is_show'=>$isshow,'issort'=>0]; 
         $result[] = ['name'=>'totalamt','value'=>'TOTAL AMOUNT','is_show'=>$isshow,'issort'=>0];
         $result[] = ['name'=>'totalsale','value'=>'SALE','is_show'=>$isshow,'issort'=>0];
         $result[] = ['name'=>'RTCOMM','value'=>'RT COMM','is_show'=>$isshow,'issort'=>0];
         $result[] = ['name'=>'DISTCOMM','value'=>'DIST COMM','is_show'=>$isshow,'issort'=>0]; 
         $result[] = ['name'=>'totalcomm','value'=>'Total COMM','is_show'=>$isshow,'issort'=>0];
         $result[] = ['name'=>'salemcomm','value'=>'SALE(-)COMMISSION','is_show'=>$isshow,'issort'=>0];    
         return $result;
      } 


    
    static function txn_ledger_DIST_header()
    {
        $isshow = 1;
        $issort = 0; 
        
        $column[] = ['name' => 'txnid', 'value' => 'Transaction ID', 'is_show' => $isshow, 'issort' => $issort];
        $column[] = ['name' => 'dateadded', 'value' => 'Date Time', 'is_show' => $isshow, 'issort' => 0];
        $column[] = ['name' => 'narration', 'value' => 'Description', 'is_show' => $isshow, 'issort' => 0];
        // $column[] = ['name' => 'username', 'value' => 'User', 'is_show' => $isshow, 'issort' => $issort];
        $column[] = ['name' => 'cd_opening', 'value' => 'Old', 'is_show' => $isshow, 'issort' => 0];
        $column[] = ['name' => 'debit', 'value' => 'Debit', 'is_show' => $isshow, 'issort' => 0]; 
        $column[] = ['name' => 'credit', 'value' => 'Credit', 'is_show' => $isshow, 'issort' => 0]; 
        $column[] = ['name' => 'cd_closing', 'value' => 'Current', 'is_show' => $isshow, 'issort' => 0];
        $column[] = ['name' => 'dtype', 'value' => 'DEBIT/CREDIT', 'is_show' => $isshow, 'issort' => 0];  
        $column[] = ['name' => 'remarks', 'value' => 'Remarks', 'is_show' => $isshow, 'issort' => 0]; 
        return $column;
     }  

    static function txn_Refundledger_SD_header()
    {
        $isshow = 1;
        $issort = 0; 
        $column[] = ['name' => 'refunddate', 'value' => 'Refund DATE&TIME', 'is_show' => $isshow, 'issort' => $issort];
        $column[] = ['name' => 'txn_id', 'value' => 'Transaction ID', 'is_show' => $isshow, 'issort' => $issort];
        $column[] = ['name' => 'refundtxnid', 'value' => 'Refund Txn ID', 'is_show' => $isshow, 'issort' => 0]; 
        $column[] = ['name' => 'firmname', 'value' => 'FIRMNAME', 'is_show' => $isshow, 'issort' => $issort];
        $column[] = ['name' => 'amount', 'value' => 'RECH Amount', 'is_show' => $isshow, 'issort' => 0];
        $column[] = ['name' => 'sdcomm', 'value' => 'DEBIT SD.COMM', 'is_show' => $isshow, 'issort' => 0]; 
        $column[] = ['name' => 'dcomm', 'value' => 'DEBIT DIST.COMM', 'is_show' => $isshow, 'issort' => 0];  
        $column[] = ['name' => 'remarks', 'value' => 'Remarks', 'is_show' => $isshow, 'issort' => 0]; 
        return $column;
    }
    static function txn_Refundledger_DIST_header()
    {
        $isshow = 1;
        $issort = 0;  
        $column[] = ['name' => 'refunddate', 'value' => 'Refund DATE&TIME', 'is_show' => $isshow, 'issort' => $issort];
        $column[] = ['name' => 'txn_id', 'value' => 'Transaction ID', 'is_show' => $isshow, 'issort' => $issort];
        $column[] = ['name' => 'refundtxnid', 'value' => 'Refund Txn ID', 'is_show' => $isshow, 'issort' => 0]; 
        $column[] = ['name' => 'firmname', 'value' => 'FIRMNAME', 'is_show' => $isshow, 'issort' => $issort];
        $column[] = ['name' => 'amount', 'value' => 'RECH Amount', 'is_show' => $isshow, 'issort' => 0]; 
        $column[] = ['name' => 'dcomm', 'value' => 'DEBIT DIST.COMM', 'is_show' => $isshow, 'issort' => 0];  
        $column[] = ['name' => 'remarks', 'value' => 'Remarks', 'is_show' => $isshow, 'issort' => 0]; 
        return $column;
    }
    static function txn_Refundledger_Super_header()
    {
        $isshow = 1;
        $issort = 0; 
        $column[] = ['name' => 'refunddate', 'value' => 'Refund DATE&TIME', 'is_show' => $isshow, 'issort' => $issort];
        $column[] = ['name' => 'txn_id', 'value' => 'Transaction ID', 'is_show' => $isshow, 'issort' => $issort];
        $column[] = ['name' => 'refundtxnid', 'value' => 'Refund Txn ID', 'is_show' => $isshow, 'issort' => 0]; 
        $column[] = ['name' => 'firmname', 'value' => 'FIRMNAME', 'is_show' => $isshow, 'issort' => $issort];
        $column[] = ['name' => 'amount', 'value' => 'RECH Amount', 'is_show' => $isshow, 'issort' => 0];
        $column[] = ['name' => 'sdcomm', 'value' => 'DEBIT SD.COMM', 'is_show' => $isshow, 'issort' => 0]; 
        $column[] = ['name' => 'dcomm', 'value' => 'DEBIT DIST.COMM', 'is_show' => $isshow, 'issort' => 0];  
        $column[] = ['name' => 'remarks', 'value' => 'Remarks', 'is_show' => $isshow, 'issort' => 0]; 
        return $column;
    }
}
