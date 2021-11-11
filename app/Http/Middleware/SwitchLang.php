<?php

namespace App\Http\Middleware;

use App\User;
use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class SwitchLang
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
        if(\Auth::user()) {
            if (!in_array(\Auth::user()->lang, ['vi', 'bgh']) && Auth::user()->level === User::LEVEL_USER) {
                App::setLocale(\Auth::user()->lang);
            }
        }
        return $next($request);
    }
}
