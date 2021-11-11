<?php

namespace App\Http\Middleware;

use App\User;
use Closure;
use Illuminate\Support\Facades\Auth;

class CheckLevelUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::user() == null) {
            return redirect('/login');
        } else if (Auth::user()->level == User::LEVEL_ADMIN) {
            return $next($request);
        }else{
            return abort(403, 'Permision Denine');
        }
    }
}
