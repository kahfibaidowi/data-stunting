<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Stunting4118Model;
use App\Models\UserModel;
use App\Models\IntervensiRencanaBantuanModel;
use App\Repository\Stunting4118Repo;
use App\Repository\IntervensiRencanaBantuanRepo;

class IntervensiRencanaBantuanController extends Controller
{

    public function add(Request $request)
    {
        $login_data=$request['fm__login_data'];
        $req=$request->all();

        //ROLE AUTHENTICATION
        if(!in_array($login_data['role'], ['admin', 'dinas'])){
            return response()->json([
                'error' =>"ACCESS_NOT_ALLOWED"
            ], 403);
        }

        //VALIDATION
        $validation=Validator::make($req, [
            'id_user'   =>[
                "required",
                Rule::exists("App\Models\UserModel")->where(function($q)use($req, $login_data){
                    if($login_data['role']=="dinas"){
                        return $q->where("role", "dinas")
                            ->where("id_user", $login_data['id_user']);
                    }
                    return $q->where("role", "dinas");
                })
            ],
            "tahun"     =>"required|date_format:Y",
            "bantuan"   =>"required",
            "harga_satuan"  =>"required|numeric|min:0",
            "satuan"    =>"required",
            "qty"       =>"required|numeric|min:0",
            "detail_kegiatan"   =>[Rule::requiredIf(!isset($req['detail_kegiatan']))]
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()->first()
            ], 500);
        }

        //SUCCESS
        DB::transaction(function()use($req){
            IntervensiRencanaBantuanModel::create([
                'id_user'   =>$req['id_user'],
                'tahun'     =>$req['tahun'],
                'bantuan'   =>$req['bantuan'],
                'harga_satuan'  =>$req['harga_satuan'],
                'satuan'    =>$req['satuan'],
                'qty'       =>$req['qty'],
                'detail_kegiatan'   =>$req['detail_kegiatan'],
                'jumlah'    =>$req['harga_satuan']*$req['qty']
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
        if(!in_array($login_data['role'], ['admin', 'dinas'])){
            return response()->json([
                'error' =>"ACCESS_NOT_ALLOWED"
            ], 403);
        }

        //VALIDATION
        $req['id_rencana_bantuan']=$id;
        $validation=Validator::make($req, [
            'id_rencana_bantuan'=>[
                "required",
                Rule::exists("App\Models\IntervensiRencanaBantuanModel")->where(function($q)use($req, $login_data){
                    if($login_data['role']=="dinas"){
                        return $q->where("id_user", $login_data['id_user']);
                    }
                })
            ],
            "bantuan"   =>"required",
            "harga_satuan"  =>"required|numeric|min:0",
            "satuan"    =>"required",
            "qty"       =>"required|numeric|min:0",
            "detail_kegiatan"   =>[Rule::requiredIf(!isset($req['detail_kegiatan']))]
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()->first()
            ], 500);
        }

        //SUCCESS
        DB::transaction(function()use($req){
            IntervensiRencanaBantuanModel::where("id_rencana_bantuan", $req['id_rencana_bantuan'])
                ->update([
                    'bantuan'   =>$req['bantuan'],
                    'harga_satuan'  =>$req['harga_satuan'],
                    'satuan'    =>$req['satuan'],
                    'qty'       =>$req['qty'],
                    'detail_kegiatan'   =>$req['detail_kegiatan'],
                    'jumlah'    =>$req['harga_satuan']*$req['qty']
                ]);
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
        if(!in_array($login_data['role'], ['admin', 'dinas'])){
            return response()->json([
                'error' =>"ACCESS_NOT_ALLOWED"
            ], 403);
        }

        //VALIDATION
        $req['id_rencana_bantuan']=$id;
        $validation=Validator::make($req, [
            'id_rencana_bantuan'=>[
                "required",
                Rule::exists("App\Models\IntervensiRencanaBantuanModel")->where(function($q)use($req, $login_data){
                    if($login_data['role']=="dinas"){
                        return $q->where("id_user", $login_data['id_user']);
                    }
                })
            ]
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()->first()
            ], 500);
        }

        //SUCCESS
        DB::transaction(function()use($req){
            IntervensiRencanaBantuanModel::where("id_rencana_bantuan", $req['id_rencana_bantuan'])->delete();
        });

        return response()->json([
            'status'=>"ok"
        ]);
    }

    public function get(Request $request, $id)
    {
        $login_data=$request['fm__login_data'];
        $req=$request->all();

        //ROLE AUTHENTICATION
        if(!in_array($login_data['role'], ['admin', 'dinas'])){
            return response()->json([
                'error' =>"ACCESS_NOT_ALLOWED"
            ], 403);
        }

        //VALIDATION
        $req['id_rencana_bantuan']=$id;
        $validation=Validator::make($req, [
            'id_rencana_bantuan'=>[
                "required",
                Rule::exists("App\Models\IntervensiRencanaBantuanModel")->where(function($q)use($req, $login_data){
                    if($login_data['role']=="dinas"){
                        return $q->where("id_user", $login_data['id_user']);
                    }
                })
            ]
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()->first()
            ], 500);
        }

        //SUCCESS
        $bantuan=IntervensiRencanaBantuanRepo::get_bantuan($req['id_rencana_bantuan']);

        return response()->json([
            'data'  =>$bantuan
        ]);
    }

    public function gets(Request $request)
    {
        $login_data=$request['fm__login_data'];
        $req=$request->all();

        //ROLE AUTHENTICATION
        if(!in_array($login_data['role'], ['admin', 'dinas'])){
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
            'tahun'     =>"required|date_format:Y",
            'id_user'   =>[
                "required",
                Rule::exists("App\Models\UserModel")->where(function($q)use($req, $login_data){
                    if($login_data['role']=="dinas"){
                        return $q->where("role", "dinas")
                            ->where("id_user", $login_data['id_user']);
                    }
                    return $q->where("role", "dinas");
                })
            ]
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()->first()
            ], 500);
        }

        //SUCCESS
        $bantuan=IntervensiRencanaBantuanRepo::gets_bantuan($req);

        return response()->json([
            'first_page'    =>1,
            'current_page'  =>$bantuan['current_page'],
            'last_page'     =>$bantuan['last_page'],
            'data'          =>$bantuan['data']
        ]);
    }
}