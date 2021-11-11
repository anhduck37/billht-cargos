<?php

namespace App\Http\Middleware;

use Closure;

class VerifyRequest
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
        $data       = $request->all();
        $stringData = '';

        foreach ($data as $index => $item) {
            if (!is_array($item) && $index != 'hmac'){
                $stringData .= $item;
            };
        };
        $stringData .= strlen($stringData);
        $keyHmac = config('endpoint.privateKey');
        $stringDataEncode = hash_hmac('md5', $stringData, $keyHmac,false);
        return $next($request);
        if($stringDataEncode === $data['hmac']){
            return $next($request);
        } else {
            return response()->json(['status' => false ,'err' => 403,'message' => 'Permision Denine'], 403);
        }
    }
}
