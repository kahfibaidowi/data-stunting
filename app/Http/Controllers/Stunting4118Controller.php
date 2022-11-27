<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Stunting4118Model;
use App\Models\UserModel;
use App\Repository\Stunting4118Repo;

class Stunting4118Controller extends Controller
{

    public function add_multiple(Request $request)
    {
        $login_data=$request['fm__login_data'];
        $req=$request->all();

        //ROLE AUTHENTICATION
        if(!in_array($login_data['role'], ['admin', 'dinkes'])){
            return response()->json([
                'error' =>"ACCESS_NOT_ALLOWED"
            ], 403);
        }

        //VALIDATION
        $validation=Validator::make($req, [
            'id_kecamatan'       =>[
                "required",
                Rule::exists("App\Models\RegionModel", "id_region")->where(function($q){
                    return $q->where("type", "kecamatan");
                })
            ],
            'skrining'              =>"required|array",
            'skrining.*.data_anak'             =>"required|required_array_keys:ayah,ibu",
            'skrining.*.data_anak.nik'         =>"required",
            'skrining.*.data_anak.tgl_lahir'   =>"required|date_format:Y-m-d",
            'skrining.*.data_anak.jenis_kelamin'=>"required|in:L,P"
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()->first()
            ], 500);
        }

        //SUCCESS
        DB::transaction(function()use($req){
            $date=date("Y-m-d");

            foreach($req['skrining'] as $val){
                //params
                //$umur=count_month($val['data_anak']['tgl_lahir'], $date);

                //update
                Stunting4118Model::create([
                    'id_user'   =>null,
                    'id_kecamatan'  =>$req['id_kecamatan'],
                    'data_anak' =>$val['data_anak'],
                    'berat_badan_lahir' =>null,
                    'tinggi_badan_lahir'=>null,
                    'berat_badan'   =>null,
                    'tinggi_badan'  =>null,
                    'usia_saat_ukur'=>null,
                    'hasil_tinggi_badan_per_umur'       =>"",
                    'hasil_berat_badan_per_umur'        =>"",
                    'hasil_berat_badan_per_tinggi_badan'=>""
                ]);
            }
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
        if(!in_array($login_data['role'], ['admin', 'dinkes'])){
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
                Rule::requiredIf(!isset($req['district_id'])),
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
        $skrining=Stunting4118Repo::gets_skrining($req);

        return response()->json([
            'first_page'    =>1,
            'current_page'  =>$skrining['current_page'],
            'last_page'     =>$skrining['last_page'],
            'data'          =>$skrining['data']
        ]);
    }

    public function gets_stunting_by_kecamatan(Request $request)
    {
        $login_data=$request['fm__login_data'];
        $req=$request->all();

        //ROLE AUTHENTICATION
        if(!in_array($login_data['role'], ['admin', 'dinkes'])){
            return response()->json([
                'error' =>"ACCESS_NOT_ALLOWED"
            ], 403);
        }

        //SUCCESS
        $stunting=Stunting4118Repo::gets_stunting_by_region_kecamatan($req);
        $center=Stunting4118Repo::get_region_center();

        return response()->json([
            'data'  =>$stunting,
            'center'=>$center
        ]);
    }
}