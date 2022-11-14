<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\RegionModel;
use App\Repository\RegionRepo;

class RegionController extends Controller
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
            'nested'    =>[
                Rule::requiredIf(function()use($req){
                    if(!isset($req['type'])) return true;
                    if(!isset($req['nested'])) return true;
                    if($req['type']=="desa"&&trim($req['nested'])=="") return true;
                    return false;
                }),
                function($attr, $value, $fail)use($req){
                    if(!isset($req['type']) || !isset($req['nested'])) return $fail("Nested error.");
                    if($req['type']=="kecamatan"&&trim($req['nested'])!="") return $fail("Nested must empty.");
                },
                Rule::exists("App\Models\RegionModel", "id_region")->where(function($q)use($req){
                    return $q->where("type", "kecamatan");
                })
            ],
            'type'      =>"required|in:kecamatan,desa",
            'region'    =>"required",
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()->first()
            ], 500);
        }

        //SUCCESS
        DB::transaction(function()use($req){
            RegionModel::create([
                'nested'=>trim($req['nested'])!=""?$req['nested']:null,
                'type'  =>$req['type'],
                'region'=>$req['region']
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
        $req['id_region']=$id;
        $validation=Validator::make($req, [
            'id_region' =>"required|exists:App\Models\RegionModel,id_region",
            'region'    =>"required",
            'nested'    =>[
                Rule::requiredIf(function()use($req){
                    $v=RegionModel::where("id_region", $req['id_region'])->first();

                    if(is_null($v) || !isset($req['nested'])) return true;
                    if($v['type']=="desa"&&trim($req['nested'])=="") return true;
                    return false;
                }),
                function($attr, $value, $fail)use($req){
                    $v=RegionModel::where("id_region", $req['id_region'])->first();

                    if(is_null($v) || !isset($req['nested'])) return $fail("Nested error.");
                    if($v['type']=="kecamatan"&&trim($req['nested'])!="") return $fail("Nested must empty.");
                },
                Rule::exists("App\Models\RegionModel", "id_region")->where(function($q)use($req){
                    return $q->where("type", "kecamatan");
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
            RegionModel::where("id_region", $req['id_region'])
                ->update([
                    'nested'=>trim($req['nested'])!=""?$req['nested']:null,
                    'region'=>$req['region']
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
        if(!in_array($login_data['role'], ['admin'])){
            return response()->json([
                'error' =>"ACCESS_NOT_ALLOWED"
            ], 403);
        }

        //VALIDATION
        $req['id_region']=$id;
        $validation=Validator::make($req, [
            'id_region'=>"required|exists:App\Models\RegionModel,id_region"
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()
            ], 500);
        }

        //SUCCESS
        DB::transaction(function()use($req){
            RegionModel::where("id_region", $req['id_region'])->delete();
        });

        return response()->json([
            'status'=>"ok"
        ]);
    }

    //GET
    public function gets_kecamatan(Request $request)
    {
        $login_data=$request['fm__login_data'];
        $req=$request->all();

        //ROLE AUTHENTICATION
        if(false){
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
            'with_desa' =>"required|boolean",
            'with_posyandu' =>"required|boolean"
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()
            ], 500);
        }

        //SUCCESS
        $region=RegionRepo::gets_kecamatan($req);

        return response()->json([
            'first_page'    =>1,
            'current_page'  =>$region['current_page'],
            'last_page'     =>$region['last_page'],
            'data'          =>$region['data']
        ]);
    }

    public function gets_desa(Request $request)
    {
        $login_data=$request['fm__login_data'];
        $req=$request->all();

        //ROLE AUTHENTICATION
        if(false){
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
            'district_id'   =>[
                "required",
                Rule::exists("App\Models\RegionModel", "id_region")->where(function($q){
                    return $q->where("type", "kecamatan");
                })
            ]
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()
            ], 500);
        }

        //SUCCESS
        $region=RegionRepo::gets_desa($req);

        return response()->json([
            'first_page'    =>1,
            'current_page'  =>$region['current_page'],
            'last_page'     =>$region['last_page'],
            'data'          =>$region['data']
        ]);
    }

    public function get(Request $request, $id)
    {
        $login_data=$request['fm__login_data'];
        $req=$request->all();

        //ROLE AUTHENTICATION
        if(false){
            return response()->json([
                'error' =>"ACCESS_NOT_ALLOWED"
            ], 403);
        }

        //VALIDATION
        $req['id_region']=$id;
        $validation=Validator::make($req, [
            'id_region'=>"required|exists:App\Models\RegionModel,id_region"
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()
            ], 500);
        }

        //SUCCESS
        $region=RegionRepo::get_region($req['id_region']);

        return response()->json([
            'data'  =>$region
        ]);
    }
}