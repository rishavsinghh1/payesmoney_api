<?php

namespace App\Http\Middleware;

use App\Models\AdminMenu;
use App\Models\AdminMenuPermission;
use Closure;
use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        
        $user = Auth::user()->id;
        $role = Auth::user()->role;
        if ($user && $role) {
            if ($role == 1) {
                $response = $next($request);
                return $response;
            }
            if (isset($request->route()[1]['as'])) {
                $permission = $request->route()[1]['as'];
                if ($permission) {
                    $isPermission = AdminMenu::where('guard_name', $permission)->first();
                    if($isPermission){
                        $userHavePermission = AdminMenuPermission::where('user_id', $user)->where('menu_id', $isPermission->id)->where('status', 1)->first();

                        if ($userHavePermission) {
                            $response = $next($request);
                            return $response;
                        } else {
                            return response('Access denied!', 403);
                        }
                    }else{
                        return response('Access denied!', 403);
                    }
                } else {
                    return response('Access denied!', 403);
                }
            }
            return response('Access denied!', 403);
        } else {
            return response('Access denied!', 403);
        }

    }
}
