<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\SkriningBalitaModel;
use App\Models\UserModel;
use App\Repository\Stunting4118Repo;
use App\Repository\IntervensiRealisasiBantuanRepo;
use App\Repository\UserRepo;

class HomeController extends Controller
{

    public function gets_stunting_by_kecamatan(Request $request)
    {
        $req=$request->all();

        //SUCCESS
        $stunting=Stunting4118Repo::gets_stunting_by_region_kecamatan($req);
        $center=Stunting4118Repo::get_region_center();

        return response()->json([
            'data'  =>$stunting,
            'center'=>$center
        ]);
    }

    public function gets_realisasi_bantuan_dinas_by_tahun(Request $request)
    {
        $req=$request->all();

        //VALIDATION
        $validation=Validator::make($req, [
            'last_year'  =>"required|in:5,10,15,20"
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()->first()
            ], 500);
        }

        //SUCCESS
        $bantuan=IntervensiRealisasiBantuanRepo::gets_realisasi_bantuan_dinas_by_tahun($req);

        return response()->json([
            'data'  =>$bantuan
        ]);
    }

    public function gets_realisasi_bantuan_tahun_by_dinas(Request $request)
    {
        $req=$request->all();

        //VALIDATION
        $validation=Validator::make($req, [
            'tahun' =>[
                Rule::requiredIf(!isset($req['tahun'])),
                "date_format:Y"
            ]
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()->first()
            ], 500);
        }

        //SUCCESS
        $bantuan=IntervensiRealisasiBantuanRepo::gets_realisasi_bantuan_tahun_by_dinas($req);

        return response()->json([
            'data'  =>$bantuan
        ]);
    }

    public function gets_skrining_data_masuk(Request $request)
    {
        $req=$request->all();

        //VALIDATION
        $validation=Validator::make($req, [
            'per_page'  =>[
                Rule::requiredIf(!isset($req['per_page'])),
                "integer",
                "min:1"
            ],
            "q"         =>[
                Rule::requiredIf(!isset($req['q']))
            ]
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()->first()
            ], 500);
        }

        //SUCCESS
        $data_masuk=UserRepo::gets_data_masuk($req);

        return response()->json([
            'first_page'    =>1,
            'current_page'  =>$data_masuk['current_page'],
            'last_page'     =>$data_masuk['last_page'],
            'data'          =>$data_masuk['data']
        ]);
    }

    public function get_summary_posyandu(Request $request)
    {
        $req=$request->all();

        //SUCCESS
        $total_posyandu=UserModel::where("role", "posyandu")->count();
        $total_skrining=SkriningBalitaModel::count();

        return response()->json([
            'total_posyandu'=>$total_posyandu,
            'total_skrining'=>$total_skrining
        ]);
    }
}