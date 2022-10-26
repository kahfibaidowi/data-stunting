<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Repository\UserLoginRepo;
use App\Models\UserLoginModel;

class UserLoginController extends Controller
{

    public function gets(Request $request)
    {
        $login_data=$request['fm__login_data'];
        $req=$request->all();

        //ROLE AUTHENTICATION
        if(!in_array($login_data['role'], ['admin'])){
            return response()->json([
                'error' =>"ACCESS_NOT_ALLOWED"
            ], 403);
        }

        //VALIDATION
        $validation=Validator::make($req, [
            'per_page'  =>[
                Rule::requiredIf(!isset($req['per_page'])),
                'integer',
                'min:1'
            ],
            'q'         =>[
                Rule::requiredIf(!isset($req['q']))
            ],
            'token_status'=>[
                Rule::requiredIf(!isset($req['token_status'])),
                Rule::in(["expired", "not_expired"])
            ]
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()
            ], 500);
        }

        //SUCCESS
        $users_login=UserLoginRepo::gets_user_login($req);

        return response()->json([
            'first_page'    =>1,
            'current_page'  =>$users_login['current_page'],
            'last_page'     =>$users_login['last_page'],
            'data'          =>$users_login['data']
        ]);
    }
    
    public function delete(Request $request, $id)
    {
        $login_data=$request['fm__login_data'];
        $req=$request->all();

        //ROLE AUTHENTICATION
        if(!in_array($login_data['role'], ['admin'])){
            return response()->json([
                'error' =>"ACCESS_NOT_ALLOWED"
            ], 403);
        }

        //VALIDATION
        $req['id_user_login']=$id;
        $validation=Validator::make($req, [
            'id_user_login' =>"required|exists:App\Models\UserLoginModel,id_user_login"
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()
            ], 500);
        }

        //SUCCESS
        DB::transaction(function()use($req){
            UserLoginModel::where("id_user_login", $req['id_user_login'])->delete();
        });

        return response()->json([
            'status'=>"ok"
        ]);
    }
    
    public function delete_expired(Request $request)
    {
        $login_data=$request['fm__login_data'];
        $req=$request->all();

        //ROLE AUTHENTICATION
        if(!in_array($login_data['role'], ['admin'])){
            return response()->json([
                'error' =>"ACCESS_NOT_ALLOWED"
            ], 403);
        }

        //SUCCESS
        DB::transaction(function()use($req){
            UserLoginModel::where("expired", "<", date("Y-m-d H:i:s"))->delete();
        });

        return response()->json([
            'status'=>"ok"
        ]);
    }
}