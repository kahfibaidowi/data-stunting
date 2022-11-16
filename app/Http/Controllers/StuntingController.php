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
}