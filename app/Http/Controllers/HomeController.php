<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\SkriningBalitaModel;
use App\Repository\Stunting4118Repo;
use App\Repository\IntervensiRealisasiBantuanRepo;

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
}