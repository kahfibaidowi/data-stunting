<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use App\Models\UserModel;
use App\Models\UserLoginModel;

class Authenticate{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $token=null_to_empty($request->bearerToken());
        try{
            $decoded=JWT::decode($token, new Key(env("JWT_SECRET"), env("JWT_ALGORITM")));

            if($decoded){
                $uid=$decoded->uid;

                $user=UserModel::where("id_user", $uid);
                $user_login=UserLoginModel::where("id_user", $uid);

                //USER NONACTIVE
                $user_data=$user->first();
                if($user_data['status']!="active"){
                    return response()->json([
                        'error'     =>"USER_SUSPENDED"
                    ], 401);
                }

                //TOKEN EXPIRED
                $found_token=[
                    'found'=>false, 
                    'data'=>[]
                ];
                foreach($user_login->get() as $v){
                    if(Crypt::decryptString($v['login_token'])==$token){
                        $found_token=[
                            'found' =>true,
                            'data'  =>$v
                        ];
                        break;
                    }
                }
                if(!$found_token['found']){
                    return response()->json([
                        'error'     =>"TOKEN_EXPIRED"
                    ], 401);
                }
                
                //SUCCESS AUTHENTICATED
                UserLoginModel::where("id_user_login", $found_token['data']['id_user_login'])
                    ->update([
                        'device_info'   =>$_SERVER['HTTP_USER_AGENT']
                    ]);
                
                return $next($request->merge([
                    'fm__login_data'    =>$user_data
                ]));
            }
        }
        catch(\Exception $e){
            //TOKEN EXPIRED
            return response()->json([
                'error'     =>"TOKEN_EXPIRED"
            ], 401);
        }
    }
}