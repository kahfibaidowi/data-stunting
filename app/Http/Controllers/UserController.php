<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Repository\UserRepo;
use App\Models\UserModel;

class UserController extends Controller
{

    public function add(Request $request)
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
            'id_region'     =>[
                Rule::requiredIf(function()use($req){
                    if(!isset($req['role'])) return true;
                    if(!isset($req['id_region'])) return true;
                    if($req['role']=="posyandu" && trim($req['id_region'])=="") return true;
                    return false;
                }),
                function($attr, $value, $fail)use($req){
                    if(!isset($req['role']) || !isset($req['id_region'])) return $fail("Id region error.");
                    if($req['role']!="posyandu" && trim($req['id_region'])!="") return $fail("Id region must empty.");
                },
                Rule::exists("App\Models\RegionModel", "id_region")->where(function($q){
                    return $q->where("type", "desa");
                })
            ],
            'username'      =>"required|unique:App\Models\UserModel,username",
            'nama_lengkap'  =>"required",
            'password'      =>"required|min:5",
            'role'          =>"required|in:admin,dinkes,posyandu,dinas",
            "avatar_url"    =>[
                Rule::requiredIf(!isset($req['avatar_url']))
            ],
            "status"        =>"required|in:active,suspend"
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()->first()
            ], 500);
        }

        //SUCCESS
        DB::transaction(function() use($req){
            UserModel::create([
                'id_region'     =>trim($req['id_region'])!=""?$req['id_region']:null,
                'username'      =>$req['username'],
                'nama_lengkap'  =>$req['nama_lengkap'],
                'password'      =>Hash::make($req['password']),
                'role'          =>$req['role'],
                'avatar_url'    =>$req['avatar_url']
            ]);
        });

        return response()->json([
            'status'=>"ok"
        ]);
    }

    public function update(Request $request, $id)
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
        $req['id_user']=$id;
        $validation=Validator::make($req, [
            'id_user'       =>"required|exists:App\Models\UserModel,id_user",
            'username'      =>[
                'required',
                Rule::unique("App\Models\UserModel")->where(function($query)use($req){
                    return $query->where("id_user", "!=", $req['id_user']);
                })
            ],
            'nama_lengkap'  =>"required",
            'password'      =>[
                Rule::requiredIf(!isset($req['password'])),
                'min:5'
            ],
            'status'        =>[
                'required',
                Rule::in(["active", "suspend"])
            ],
            "avatar_url"    =>[
                Rule::requiredIf(!isset($req['avatar_url']))
            ]
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()->first()
            ], 500);
        }

        //SUCCESS
        $data_update=[
            'username'      =>$req['username'],
            'nama_lengkap'  =>$req['nama_lengkap'],
            'status'        =>$req['status'],
            'avatar_url'    =>$req['avatar_url']
        ];
        if($req['password']!=""){
            $data_update=array_merge($data_update, [
                'password'  =>Hash::make($req['password'])
            ]);
        }

        DB::transaction(function()use($data_update, $req){
            UserModel::where("id_user", $req['id_user'])->update($data_update);
        });

        return response()->json([
            'status'=>"ok"
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
        $req['id_user']=$id;
        $validation=Validator::make($req, [
            'id_user'   =>"required|exists:App\Models\UserModel,id_user"
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()
            ], 500);
        }

        //ME
        if($req['id_user']==$login_data['id_user']){
            return response()->json([
                'error' =>"SELF_DELETE_NOT_ALLOWED"
            ], 500);
        }

        //SUCCESS
        DB::transaction(function() use($req){
            UserModel::where("id_user", $req['id_user'])->delete();
        });

        return response()->json([
            'status'=>"ok"
        ]);
    }

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
            'role'      =>[
                Rule::requiredIf(!isset($req['role'])),
                Rule::in(['admin', 'dinkes', "posyandu", "dinas"]),
            ],
            'status'    =>[
                Rule::requiredIf(!isset($req['status'])),
                Rule::in(['active', 'suspend'])
            ]
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()
            ], 500);
        }

        //SUCCESS
        $users=UserRepo::gets_user($req);

        return response()->json([
            'first_page'    =>1,
            'current_page'  =>$users['current_page'],
            'last_page'     =>$users['last_page'],
            'data'          =>$users['data']
        ]);
    }

    public function get(Request $request, $id)
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
        $req['id_user']=$id;
        $validation=Validator::make($req, [
            'id_user'   =>"required|exists:App\Models\UserModel,id_user"
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()
            ], 500);
        }

        //SUCCESS
        $user=UserRepo::get_user($req['id_user']);
        
        return response()->json([
            'data'  =>$user
        ]);
    }
}
