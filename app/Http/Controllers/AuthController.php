<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Repository\UserRepo;
use App\Repository\UserLoginRepo;
use App\Models\UserModel;
use App\Models\UserLoginModel;

class AuthController extends Controller
{
    
    public function login(Request $request)
    {
        $req=$request->all();

        $validation=Validator::make($req, [
            'username'  =>"required",
            'password'  =>"required",
            'remember'  =>"required|boolean"
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()
            ], 500);
        }

        $user=UserRepo::get_user_by_username($req['username']);
        if(is_null($user)){
            return response()->json([
                'error' =>"USERNAME_OR_PASSWORD_WRONG"
            ], 500);
        }

        if(!Hash::check($req['password'], $user['password'])){
            return response()->json([
                'error' =>"USERNAME_OR_PASSWORD_WRONG"
            ], 500);
        }

        //SUCCESS
        //no_remember=12 jam, remember=7 hari
        $expired=!$req['remember']?12*3600:7*24*3600;
        $time=time();
        $token=[
            'iat'   =>$time,
            'nbf'   =>$time,
            'exp'   =>$time+$expired,
            'uid'   =>$user['id_user']
        ];
        $generated_token=JWT::encode($token, env("JWT_SECRET"), env("JWT_ALGORITM"));

        //insert
        DB::transaction(function () use($user, $generated_token, $time, $expired){
            UserLoginModel::create([
                'id_user'       =>$user['id_user'],
                'login_token'   =>Crypt::encryptString($generated_token),
                'expired'       =>date('Y-m-d H:i:s', $time+$expired),
                'device_info'   =>$_SERVER['HTTP_USER_AGENT']
            ]);
        });
        
        return response()->json([
            'data'  =>[
                'id_user'       =>$user['id_user'],
                'id_region'     =>$user['id_region'],
                'username'      =>$user['username'],
                'nama_lengkap'  =>$user['nama_lengkap'],
                'avatar_url'    =>$user['avatar_url'],
                'role'          =>$user['role'],
                'access_token'  =>$generated_token
            ]
        ]);
    }

    public function verify_login(Request $request)
    {
        $login_data=$request['fm__login_data'];

        //SUCCESS
        return response()->json([
            'status'=>"ok"
        ]);
    }

    public function get_profile(Request $request)
    {
        $login_data=$request['fm__login_data'];
        $req=$request->all();

        //SUCCESS
        $user=UserRepo::get_user($login_data['id_user']);

        return response()->json([
            'data'  =>$user
        ]);
    }

    public function update_profile(Request $request)
    {
        $login_data=$request['fm__login_data'];
        $req=$request->all();

        //VALIDATION
        $validation=Validator::make($req, [
            'username'  =>[
                "required",
                Rule::unique("App\Models\UserModel")->where(function($q)use($login_data){
                    return $q->where("id_user", "!=", $login_data['id_user']);
                })
            ],
            'nama_lengkap'  =>"required",
            'avatar_url'=>[
                Rule::requiredIf(!isset($req['avatar_url']))
            ],
            'password'  =>[
                Rule::requiredIf(!isset($req['password'])),
                'min:5'
            ]
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()
            ], 500);
        }

        //SUCCESS
        $data_update=[
            'username'      =>$req['username'],
            'nama_lengkap'  =>$req['nama_lengkap'],
            'avatar_url'    =>$req['avatar_url']
        ];
        if($req['password']!=""){
            $data_update=array_merge($data_update, [
                'password'  =>Hash::make($req['password'])
            ]);
        }

        //update
        DB::transaction(function () use($login_data, $data_update){
            UserModel::where("id_user", $login_data['id_user'])->update($data_update);
        });

        return response()->json([
            'status'    =>"ok"
        ]);
    }

    public function logout(Request $request)
    {
        $login_data=$request['fm__login_data'];
        $req=$request->all();

        //SUCCESS
        $user_login=UserLoginRepo::get_by_user($login_data['id_user']);
        $id_user_login=0;
        foreach($user_login as $val){
            if(Crypt::decryptString($val['login_token'])==$request->bearerToken()){
                $id_user_login=$val['id_user_login'];
            }
        }

        //delete
        DB::transaction(function() use($id_user_login){
            UserLoginModel::where("id_user_login", $id_user_login)->delete();
        });

        return response()->json([
            'status'=>"ok"
        ], 401);
    }

    //GENERATE SYSTEM TOKEN
    public function generate_kependudukan_system_token(Request $request)
    {
        $login_data=$request['fm__login_data'];
        $req=$request->all();

        //token
        $expired=60;
        $time=time();
        $token=[
            'iat'   =>$time,
            'nbf'   =>$time,
            'exp'   =>$time+$expired,
            'uid'   =>""
        ];
        $generated_token=JWT::encode($token, env("JWT_SECRET_KEPENDUDUKAN_SYSTEM"), env("JWT_ALGORITM"));

        return response()->json([
            'data'  =>$generated_token
        ]);
    }
}