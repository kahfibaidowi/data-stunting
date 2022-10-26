<?php

namespace App\Http\Middleware;

use Closure;

class Cors{

    /**
     * Handle an incoming request.
     * Must first execute Auth Middleware
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        //Intercepts OPTIONS requests
        if($request->isMethod("OPTIONS")){
            $response=response('', 200);
        }
        else{
            $response=$next($request);
        }

        // Adds headers to the response
        $response->header('Access-Control-Allow-Methods', 'HEAD, GET, POST, PUT, PATCH, DELETE');
        $response->header('Access-Control-Allow-Headers', $request->header('Access-Control-Request-Headers'));
        $response->header('Access-Control-Allow-Origin', '*');

        //RETURN
        return $response;
    }
}