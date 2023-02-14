<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\BalitaSkriningModel;
use App\Models\UserModel;
use App\Repository\Stunting4118Repo;
use App\Repository\IntervensiRealisasiBantuanRepo;
use App\Repository\UserRepo;

class HomeController extends Controller
{

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
        $total_skrining=BalitaSkriningModel::count();

        return response()->json([
            'total_posyandu'=>$total_posyandu,
            'total_skrining'=>$total_skrining
        ]);
    }
}