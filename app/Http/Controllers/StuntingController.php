<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\SkriningBalitaModel;
use App\Repository\StuntingRepo;

class StuntingController extends Controller
{

    public function gets_stunting_by_region(Request $request)
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
            'type'      =>"required|in:kecamatan,desa",
            'district_id'=>[
                Rule::requiredIf(!isset($req['district_id'])),
                Rule::exists("App\Models\RegionModel", "id_region")->where(function($q)use($req){
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
        if($req['type']=="desa"){
            $stunting=StuntingRepo::gets_stunting_by_region_desa($req);
        }
        else{
            $stunting=StuntingRepo::gets_stunting_by_region_kecamatan($req);
        }
        $center=StuntingRepo::get_region_center($req['district_id']);

        return response()->json([
            'data'  =>$stunting,
            'center'=>$center
        ]);
    }

    public function gets_stunting(Request $request)
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
            'posyandu_id'   =>[
                Rule::requiredIf(function()use($req, $login_data){
                    if(!isset($req['posyandu_id'])) return true;
                    if($login_data['role']=="posyandu") return true;
                    return false;
                }),
                function($attr, $value, $fail)use($req, $login_data){
                    if(!isset($req['posyandu_id'])) return $fail("Posyandu id error.");
                    if($req['posyandu_id']!=$login_data['id_user']&&$login_data['role']=="posyandu") return $fail("Posyandu id error.");
                    return true;
                },
                Rule::exists("App\Models\UserModel", "id_user")
            ]
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()
            ], 500);
        }

        //SUCCESS
        $stunting=StuntingRepo::gets_stunting($req);

        return response()->json([
            'first_page'    =>1,
            'current_page'  =>$stunting['current_page'],
            'last_page'     =>$stunting['last_page'],
            'data'          =>$stunting['data']
        ]);
    }
}