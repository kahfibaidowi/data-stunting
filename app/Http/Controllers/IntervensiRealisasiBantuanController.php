<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Stunting4118Model;
use App\Models\UserModel;
use App\Models\IntervensiRealisasiBantuanModel;
use App\Repository\Stunting4118Repo;
use App\Repository\IntervensiRealisasiBantuanRepo;

class IntervensiRealisasiBantuanController extends Controller
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
            'id_skrining_balita'=>"required|exists:App\Models\Stunting4118Model,id_skrining_balita",
            'id_rencana_bantuan'=>[
                "required",
                Rule::exists("App\Models\IntervensiRencanaBantuanModel")->where(function($q)use($req, $login_data){
                    if($login_data['role']=="dinas"){
                        return $q->where("id_user", $login_data['id_user']);
                    }
                })
            ],
            "nominal"           =>"required|numeric|min:0",
            "dokumen"           =>[
                Rule::requiredIf(!isset($req['dokumen']))
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
            IntervensiRealisasiBantuanModel::create([
                'id_skrining_balita'=>$req['id_skrining_balita'],
                'id_rencana_bantuan'=>$req['id_rencana_bantuan'],
                'nominal'           =>$req['nominal'],
                'dokumen'           =>$req['dokumen']
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
        $req['id_realisasi_bantuan']=$id;
        $validation=Validator::make($req, [
            'id_realisasi_bantuan'  =>[
                "required",
                function($attr, $value, $fail)use($req, $login_data){
                    if(!isset($value)) return $fail("id_realisasi_bantuan required.");

                    $v=IntervensiRealisasiBantuanModel::where("id_realisasi_bantuan", $value)
                        ->whereHas("rencana_bantuan", function($q)use($req, $login_data){
                            if($login_data['role']=="dinas"){
                                return $q->where("id_user", $login_data['id_user']);
                            }
                        });
                    
                    if(is_null($v->first())) return $fail("id_realiasasi_bantuan is invalid.");

                    return true;
                }
            ],
            "nominal"               =>"required|numeric|min:0",
            "dokumen"           =>[
                Rule::requiredIf(!isset($req['dokumen']))
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
            IntervensiRealisasiBantuanModel::where("id_realisasi_bantuan", $req['id_realisasi_bantuan'])
                ->update([
                    'nominal'   =>$req['nominal'],
                    'dokumen'   =>$req['dokumen']
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
        $req['id_realisasi_bantuan']=$id;
        $validation=Validator::make($req, [
            'id_realisasi_bantuan'  =>[
                "required",
                function($attr, $value, $fail)use($req, $login_data){
                    if(!isset($value)) return $fail("id_realisasi_bantuan required.");

                    $v=IntervensiRealisasiBantuanModel::where("id_realisasi_bantuan", $value)
                        ->whereHas("rencana_bantuan", function($q)use($req, $login_data){
                            if($login_data['role']=="dinas"){
                                return $q->where("id_user", $login_data['id_user']);
                            }
                        });
                    
                    if(is_null($v->first())) return $fail("id_realiasasi_bantuan is invalid.");

                    return true;
                }
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
            IntervensiRealisasiBantuanModel::where("id_realisasi_bantuan", $req['id_realisasi_bantuan'])->delete();
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
        $req['id_realisasi_bantuan']=$id;
        $validation=Validator::make($req, [
            'id_realisasi_bantuan'  =>[
                "required",
                function($attr, $value, $fail)use($req, $login_data){
                    if(!isset($value)) return $fail("id_realisasi_bantuan required.");

                    $v=IntervensiRealisasiBantuanModel::where("id_realisasi_bantuan", $value)
                        ->whereHas("rencana_bantuan", function($q)use($req, $login_data){
                            if($login_data['role']=="dinas"){
                                return $q->where("id_user", $login_data['id_user']);
                            }
                        });
                    
                    if(is_null($v->first())) return $fail("id_realiasasi_bantuan is invalid.");

                    return true;
                }
            ]
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()->first()
            ], 500);
        }

        //SUCCESS
        $bantuan=IntervensiRealisasiBantuanRepo::get_bantuan($req['id_realisasi_bantuan']);

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
        $bantuan=IntervensiRealisasiBantuanRepo::gets_bantuan($req);

        return response()->json([
            'first_page'    =>1,
            'current_page'  =>$bantuan['current_page'],
            'last_page'     =>$bantuan['last_page'],
            'data'          =>$bantuan['data']
        ]);
    }
}