<?php
/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
 */
$router->get('/', function () use ($router) {
    return $router->app->version();
});
$router->get('recharge/payesmoney', 'Recharge\Prepaidrecharge@callbackrecharge');
$router->get('superdistributor','ConfigurationController@superdistributor'); 
$router->get('distributor','ConfigurationController@distributor'); 
$router->get('Refundsd','ConfigurationController@Refundsd'); 
 
// $router->group(['middleware' => 'checkip'], function () use ($router) { 
    //auth routes
    $router->group(['prefix' => 'auth'], function () use ($router) {
        $router->post('login', 'Auth\LoginController@index');
        $router->post('sendOtp', 'Auth\ResetController@sendOtp');
        $router->post('verifyOtp', 'Auth\ResetController@verifyOtp');
        $router->post('forgotPassword', 'Auth\ResetController@forgotPassword');
        $router->post('forgotPasswordEmail', 'Auth\ResetController@forgotPasswordMail');
        $router->post('addmpin', 'Auth\LoginController@addmpin');
        $router->post('addtpin', 'Auth\LoginController@addtpin');
        $router->post('changeMpin', 'Auth\LoginController@changeMpin');
        $router->post('changeTpin', 'Auth\LoginController@changeTpin');
        
        $router->group(['middleware' => ['auth:api']], function () use ($router) { 
            $router->post('logout', 'Auth\LoginController@logout');
        });
        $router->group(['middleware' => ['auth:api']], function () use ($router) {
            $router->post('changePassword', ['as' => 'change-Password', 'uses' => 'Auth\ResetController@changePassword']);
            $router->post('user-permissions', 'Auth\LoginController@userPermissions');
            $router->post('leftPanel', ['as' => 'left-panel','uses' => 'Auth\LoginController@getLeftPanel']);
            
        });
    });
    $router->post('search-merchant', ['as' => 'search-merchant', 'uses' => 'User\UserController@searchUser']);
    $router->group(['middleware' => ['auth:api']], function () use ($router) {
        $router->post('admin-parent-menu', ['as' => 'admin-parent-menu', 'uses' => 'Master\AdminConfigController@adminParentMenu']);
        $router->post('front-parent-menu', ['as' => 'front-parent-menu', 'uses' => 'Master\FrontSidebarController@frontParentMenu']);
    });
    $router->group(['middleware' => ['auth:api','checkperm']], function () use ($router) { 
        $router->group(['prefix' => 'user'], function () use ($router) {
            $router->post('register', ['as' => 'user-register', 'uses' => 'Auth\RegisterController@register']);
            $router->post('list-user', ['as' => 'user-list', 'uses' => 'User\UserController@listUsers']); 
            $router->post('update-user', ['as' => 'user-update', 'uses' => 'User\UserController@updateUser']);
            $router->post('assigned-modules', ['as' => 'assigned-modules', 'uses' => 'Master\AdminConfigController@getModulePermission']);
            $router->post('assign-modules', ['as' => 'user-module-assign', 'uses' => 'Master\AdminConfigController@updateModulePermission']);
            $router->post('role-modules', ['as' => 'role-modules', 'uses' => 'Master\AdminConfigController@getRoleModules']);
            $router->post('update-role-modules', ['as' => 'update-role-modules', 'uses' => 'Master\AdminConfigController@updateRoleModules']);
            $router->post('get-module-items', ['as' => 'get-module-items', 'uses' => 'Master\AdminConfigController@getModuleItem']);
            $router->post('update-module-items', ['as' => 'update-module-items', 'uses' => 'Master\AdminConfigController@updateModuleItem']);
        });
        $router->group(['prefix' => 'bussiness-banking'], function () use ($router) {
            $router->post('bank-list', ['as' => 'business-bank', 'uses' => 'BussinessBanking\BankController@bankList']);
            $router->post('get-bussiness-bank', ['as' => 'business-bank', 'uses' => 'BussinessBanking\BankController@viewBank']);
            $router->post('add-bussiness-bank', ['as' => 'business-bank', 'uses' => 'BussinessBanking\BankController@addBank']);
            $router->post('update-bussiness-bank', ['as' => 'business-bank', 'uses' => 'BussinessBanking\BankController@update']);
            $router->post('add-bankform', ['as' => 'business-bank', 'uses' => 'BussinessBanking\BankController@add']);
            $router->post('update-bankform', ['as' => 'business-bank', 'uses' => 'BussinessBanking\BankController@updatebankform']);
            $router->post('list-bankform', ['as' => 'business-bank', 'uses' => 'BussinessBanking\BankController@listBankForm']);
            $router->post('get-bankform', ['as' => 'business-bank', 'uses' => 'BussinessBanking\BankController@showBankForm']);
            $router->post('partner-reports', ['as' => 'partner-reports', 'uses' => 'BussinessBanking\PartnerController@partnerList']);
            $router->post('update-partner', ['as' => 'partner-reports', 'uses' => 'BussinessBanking\PartnerController@updatePartner']);
            $router->post('list-cib', ['as' => 'partner-requests', 'uses' => 'BussinessBanking\PartnerRequests@cibRegistrations']);
            $router->post('get-cib', ['as' => 'partner-requests', 'uses' => 'BussinessBanking\PartnerRequests@getCib']);
            $router->post('update-cib', ['as' => 'partner-requests', 'uses' => 'BussinessBanking\PartnerRequests@updateCib']);
            $router->post('update-particular-document', ['as' => 'partner-requests', 'uses' => 'BussinessBanking\PartnerRequests@updateDocument']);
        
        });
        $router->group(['prefix' => 'charges'], function () use ($router) {
            $router->post('getcharges', ['as' => 'get-charges', 'uses' => 'Master\ChargesController@getcharges']);
            $router->post('save-charges', ['as' => 'save-charges', 'uses' => 'Master\ChargesController@savecharges']);
            $router->post('delete-charges',['as' => 'delete-charges', 'uses' => 'Master\ChargesController@deletecharges']);
            $router->post('filter',['as' => 'filter', 'uses' => 'Master\ChargesController@filter']);
        });
        
        $router->group(['prefix' => 'notification'], function () use ($router) {
            $router->post('add-notification', ['as' => 'notifications', 'uses' => 'Master\NotificationController@addNotification']);
            $router->post('update-notification', ['as' => 'notifications', 'uses' => 'Master\NotificationController@notificationUpdate']);
            $router->post('list-notification', ['as' => 'notifications', 'uses' => 'Master\NotificationController@notificationList']);
            $router->post('delete-notification', ['as' => 'notifications', 'uses' => 'Master\NotificationController@notificationDelete']);
            $router->post('send-notification', ['as' => 'notifications', 'uses' => 'Master\NotificationController@sendnotification']);
        });
        $router->group(['prefix' => 'menu'], function () use ($router) {
            $router->post('account-types', ['as' => 'account-types', 'uses' => 'Master\FrontSidebarController@accountTypes']);
            $router->post('add-item', ['as' => 'add-item', 'uses' => 'Master\FrontSidebarController@addMenuItem']);
            $router->post('list-item', ['as' => 'list-item', 'uses' => 'Master\FrontSidebarController@listMenuItem']);
            $router->post('get-item', ['as' => 'get-item', 'uses' => 'Master\FrontSidebarController@getMenuItem']);
            $router->post('update-item', ['as' => 'update-item', 'uses' => 'Master\FrontSidebarController@updateMenuItem']);
            $router->post('delete-frontmenu', ['as' => 'delete-frontmenu', 'uses' => 'Master\FrontSidebarController@deletemenu']);
            $router->post('add-role', ['as' => 'add-role', 'uses' => 'Master\FrontSidebarController@addRole']);
            $router->post('update-role', ['as' => 'update-role', 'uses' => 'Master\FrontSidebarController@updateRole']);
            $router->post('list-role', ['as' => 'list-role', 'uses' => 'Master\FrontSidebarController@listRole']);
            $router->post('get-role', ['as' => 'get-role', 'uses' => 'Master\FrontSidebarController@updateRole']);
            $router->post('delete-role', ['as' => 'delete-role', 'uses' => 'Master\FrontSidebarController@getRole']);
            $router->post('get-role-menu', ['as' => 'update-item', 'uses' => 'Master\FrontSidebarController@getRoleItems']);
            $router->post('update-role-menu', ['as' => 'update-item', 'uses' => 'Master\FrontSidebarController@updateRoleItem']);
        });

        $router->group(['prefix' => 'account'], function () use ($router) {
            $router->post('add-account', ['as' => 'add-account', 'uses' => 'Master\AccountController@accounttype']);
            $router->post('get-account', ['as' => 'get-account', 'uses' => 'Master\AccountController@showaccount']);
            $router->post('update-account', ['as' => 'update-account', 'uses' => 'Master\AccountController@updateaccount']);
            $router->post('list-account', ['as' => 'list-account', 'uses' => 'Master\AccountController@list']);
        });
        
        $router->group(['prefix' => 'module'], function () use ($router) {
            $router->post('add-module', ['as' => 'add-module', 'uses' => 'Master\AdminConfigController@addModule']);
            $router->post('update-module', ['as' => 'update-module', 'uses' => 'Master\AdminConfigController@updateModule']);
            $router->post('list-module', ['as' => 'list-module', 'uses' => 'Master\AdminConfigController@listModule']);
            $router->post('get-module', ['as' => 'get-module', 'uses' => 'Master\AdminConfigController@getModule']);
            $router->post('add-menu-item', ['as' => 'add-menu-item', 'uses' => 'Master\AdminConfigController@addMenuItem']);
            $router->post('get-menu-item', ['as' => 'get-menu-item', 'uses' => 'Master\AdminConfigController@getMenuItem']);
            $router->post('list-menu-item', ['as' => 'list-menu-item', 'uses' => 'Master\AdminConfigController@listMenuItem']);
            $router->post('update-menu-item', ['as' => 'update-menu-item', 'uses' => 'Master\AdminConfigController@updateMenuItem']);
        });

        $router->group(['prefix' => 'role'], function () use ($router) {
            $router->post('add-role', ['as' => 'add-role', 'uses' => 'Master\AdminConfigController@addRole']);
            $router->post('update-role', ['as' => 'update-role', 'uses' => 'Master\AdminConfigController@updateRole']);
            $router->post('list-role', ['as' => 'list-role', 'uses' => 'Master\AdminConfigController@listRole']);
            $router->post('get-role', ['as' => 'get-role', 'uses' => 'Master\AdminConfigController@getRole']);
        });

        $router->group(['prefix' => 'reports'], function () use ($router) {
            $router->post('upi', ['as' => 'reports-upi', 'uses' => 'Reports\UpiController@report']);
            $router->post('vpa', ['as' => 'reports-vpa', 'uses' => 'Reports\VpaController@vpa']);
            $router->post('single-vpa', ['as' => 'report-single-vpa', 'uses' => 'Reports\VpaController@singleVpa']);
            $router->post('va', ['as' => 'reports-va', 'uses' => 'Reports\VaController@list']);
            $router->post('single-va', ['as' => 'report-single-va', 'uses' => 'Reports\VaController@statement']);
            $router->post('va-transaction', ['as' => 'reports-va', 'uses' => 'Reports\VaController@transactions']);
            $router->post('bene-list', ['as' => 'payout-reports', 'uses' => 'Reports\PayoutController@list']);
            $router->post('payout-transactions', ['as' => 'payout-reports', 'uses' => 'Reports\PayoutController@statement']);
        });
        $router->group(['prefix' => 'configuration'], function () use ($router) {
            $router->post('admin', ['as' => 'admin-configuration', 'uses' => 'ConfigurationController@admin']);
            $router->post('front', ['as' => 'front-configuration', 'uses' => 'ConfigurationController@front']);
        });
    });
    $router->group(['middleware' => 'auth:api'], function () use ($router) { 


        $router->group(['prefix' => 'recharge'], function () use ($router) { 
            $router->post('dorecharge', 'Recharge\Prepaidrecharge@dorecharge');
            $router->post('dodthrecharge', 'Recharge\Dthrecharge@dorecharge');
            $router->post('rechargehistory', 'Reports\PayoutController@record'); 
            $router->post('getoperator', 'Recharge\Prepaidrecharge@getoperator'); 
            $router->post('getRoffer', 'Recharge\Prepaidrecharge@getRoffer');
            $router->post('getDthDetails', 'Recharge\Prepaidrecharge@getDthDetails');
            $router->post('dayledger', 'Reports\PayoutController@dayledger');  

            $router->post('refundReport', 'Reports\PayoutController@refundReport');
        }); 
        $router->group(['prefix' => 'manual'], function () use ($router) { 
            $router->post('updateSucesstoFailedRec', 'Recharge\ManualProcessController@updateSucesstoFailedRec'); 
        }); 
        $router->post('user/get-user', ['as' => 'get-user', 'uses' => 'User\UserController@getUser']); 
        $router->post('get-user', ['as' => 'get-user', 'uses' => 'User\UserController@getUser']);
        
        $router->group(['prefix' => 'superdist'], function () use ($router) { 
            $router->post('create', 'User\SD\SuperDistController@create'); 
            $router->post('list', 'User\SD\SuperDistController@list');   
            $router->post('superdistributor','ConfigurationController@superdistributor');
            $router->post('getsuperdistributor', 'User\UserController@getsuperdistributor');   
            
            
        });
        $router->group(['prefix' => 'dist'], function () use ($router) { 
            $router->post('create', 'User\DIST\DistController@create');
            $router->post('list', 'User\DIST\DistController@list'); 
            $router->post('distributor','ConfigurationController@distributor');  
            $router->post('getdistributor', 'User\UserController@getdistributor');  
            
            
        });
        $router->group(['prefix' => 'retailer'], function () use ($router) { 
            $router->post('create', 'User\REATILER\RetailerController@create'); 
            $router->post('list', 'User\REATILER\RetailerController@list');  
            
        });
        $router->group(['prefix' => 'ledger'], function () use ($router) { 
            $router->post('txn-ledger', 'Reports\PayoutController@ledgerrecord');   
        });
       
         
        $router->group(['prefix' => 'funding'], function () use ($router) { 
            $router->post('create', 'funding\FundingController@create');  
            $router->post('addfund', 'funding\FundingController@AdminAddFund');  
            $router->post('addfundsuperadmin', 'funding\FundingController@addfundsuperadmin');  
            $router->post('transferfund', 'funding\FundingController@transferLoadfund');  
            $router->post('approve', 'funding\FundingController@approve');  
            $router->post('getFundingRequestDetail', 'funding\FundingController@getFundingRequestDetail'); 
            $router->post('getlistfund','funding\FundingController@getpendingFund');
            $router->post('getpendingById','funding\FundingController@getpendingById');
            $router->post('getrequest', 'funding\FundingController@Getrequest');
            
        });
        $router->group(['prefix' => 'commission'], function () use ($router) {
            $router->post('list', ['as' => 'list', 'uses' => 'Commission\Template@list']); 
            $router->post('getbyid', ['as' => 'getbyid', 'uses' => 'Commission\Template@getbyid']); 
            $router->post('create', ['as' => 'create', 'uses' => 'Commission\Template@create']); 
            $router->post('update', ['as' => 'update', 'uses' => 'Commission\Template@update']);  

            $router->post('getuserlist', ['as' => 'getuserlist', 'uses' => 'Commission\Commission@getuserlist']);
            $router->post('getassignedCommission', ['as' => 'getassignedCommission', 'uses' => 'Commission\Commission@getassignedCommission']); 
            $router->post('assigntempcomm', ['as' => 'assigntempcomm', 'uses' => 'Commission\Commission@assigntempcomm']);  
        });
        $router->group(['prefix' => 'permission'], function () use ($router) {
            $router->post('list', ['as' => 'list', 'uses' => 'Permission\PermissionController@Permissionlist']);  
            $router->post('update', ['as' => 'update', 'uses' => 'Permission\PermissionController@Permissionupdate']); 
            
        });
        $router->group(['prefix' => 'company-bank'], function () use ($router) {
            $router->post('getlist', ['as' => 'list', 'uses' => 'Companybank\BankController@getlist']);  
            //$router->post('update', ['as' => 'update', 'uses' => 'Permission\PermissionController@Permissionupdate']); 
            
        });   
    }); 
 
        
    
    
// });




$router->group(['prefix' => 'api', 'middleware' => ['auth:api', 'crypt', 'logs']], function () use ($router) {
   
    /*..................... USer route........................... */
    $router->post('user/list', ['as' => 'user-list', 'uses' => 'User\UserController@index']);
    $router->post('user/update', ['as' => 'user-update', 'uses' => 'User\UserController@update']);

    /*.....................@module............................ */
    $router->post('module/add', ['as' => 'module-add', 'uses' => 'Auth\PermissionController@addModule']);
    $router->post('module/delete', ['as' => 'module-delete', 'uses' => 'Auth\PermissionController@deleteModule']);
    $router->post('module/list', ['as' => 'module-list', 'uses' => 'Auth\PermissionController@moduleList']);

    /*.....................@permission............................ */
    $router->post('permission/add', ['as' => 'permission-add', 'uses' => 'Auth\PermissionController@addCustomPermission']);
    $router->post('permission/delete', ['as' => 'permission-delete', 'uses' => 'Auth\PermissionController@deletePermission']);
    $router->post('permission/list', ['as' => 'permission-list', 'uses' => 'Auth\PermissionController@PermissionList']);
    $router->post('permission/update', ['as' => 'permission-delete', 'uses' => 'Auth\PermissionController@updatePermission']);

    /*.....................@role............................ */
    $router->post('role/add', ['as' => 'role-add', 'uses' => 'Auth\PermissionController@addRole']);
    $router->post('role/edit', ['as' => 'role-update', 'uses' => 'Auth\PermissionController@updateRole']);
    $router->post('role/delete', ['as' => 'role-delete', 'uses' => 'Auth\PermissionController@deleteRole']);
    $router->post('role/list', ['as' => 'role-list', 'uses' => 'Auth\PermissionController@RoleList']);
    $router->post('role/view', ['as' => 'role-view', 'uses' => 'Auth\PermissionController@getRoleById']);
    /*.....................@role............................ */
   
});
    