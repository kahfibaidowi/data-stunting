<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\BalitaModel;
use App\Models\UserModel;
use App\Repository\BalitaRepo;

class BalitaController extends Controller
{
    public function upsert(Request $request)
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
            "nik"               =>[
                "required",
                "max:50"
            ],
            "no_kk"             =>[
                Rule::requiredIf(!isset($req['no_kk'])),
                "max:50"
            ],
            "nama_lengkap"      =>[
                "required"
            ],
            "tempat_lahir"      =>[
                Rule::requiredIf(!isset($req['tempat_lahir']))
            ],
            "tgl_lahir"         =>[
                "required",
                "date_format:Y-m-d"
            ],
            "jenis_kelamin"     =>[
                "required",
                "in:L,P"
            ],
            "provinsi"          =>[
                Rule::requiredIf(!isset($req['provinsi'])),
            ],
            "kabupaten_kota"    =>[
                Rule::requiredIf(!isset($req['kabupaten_kota'])),
            ],
            "kecamatan"         =>[
                Rule::requiredIf(!isset($req['kecamatan']))
            ],
            "desa"              =>[
                Rule::requiredIf(!isset($req['desa']))
            ],
            "alamat_detail"     =>[
                Rule::requiredIf(!isset($req['alamat_detail']))
            ],
            "ibu"               =>"required",
            "ibu.nik"           =>"required",
            "ibu.nama_lengkap"  =>"required",
            "data_status"       =>[
                Rule::requiredIf(!isset($req['data_status']))
            ],
            "from_kependudukan" =>"required|in:y,n",
            "berat_badan_lahir" =>[
                Rule::requiredIf(!isset($req['berat_badan_lahir']))
            ],
            "tinggi_badan_lahir"=>[
                Rule::requiredIf(!isset($req['tinggi_badan_lahir']))
            ]
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()->first()
            ], 500);
        }

        //SUCCESS
        $updated=null;
        DB::transaction(function()use($req, &$updated){
            $updated=BalitaModel::updateOrCreate(
                [
                    "nik"               =>$req['nik']
                ],
                [
                    "no_kk"             =>$req['no_kk'],
                    "nama_lengkap"      =>$req['nama_lengkap'],
                    "tempat_lahir"      =>$req['tempat_lahir'],
                    "tgl_lahir"         =>$req['tgl_lahir'],
                    "jenis_kelamin"     =>$req['jenis_kelamin'],
                    "provinsi"          =>$req['provinsi'],
                    "kabupaten_kota"    =>$req['kabupaten_kota'],
                    "kecamatan"         =>$req['kecamatan'],
                    "desa"              =>$req['desa'],
                    "alamat_detail"     =>$req['alamat_detail'],
                    "ibu"               =>$req['ibu'],
                    "data_status"       =>$req['data_status'],
                    "from_kependudukan" =>$req['from_kependudukan'],
                    "berat_badan_lahir" =>trim($req['berat_badan_lahir'])!=""?$req['berat_badan_lahir']:null,
                    "tinggi_badan_lahir"=>trim($req['tinggi_badan_lahir'])!=""?$req['tinggi_badan_lahir']:null
                ]
            );
        });

        return response()->json([
            'status'=>"ok",
            'data'  =>$updated
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
        $req['id_balita']=$id;
        $validation=Validator::make($req, [
            "id_balita" =>"required|exists:App\Models\BalitaModel"
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()->first()
            ], 500);
        }

        //SUCCESS
        DB::transaction(function()use($req){
            BalitaModel::where("id_balita", $req['id_balita'])->delete();
        });

        return response()->json([
            'status'=>"ok"
        ]);
    }

    public function get(Request $request, $id)
    {
        $req=$request->all();

        $type=isset($req['type'])?$req['type']:"";
        if($type=="nik"){
            return $this->get_by_nik($request, $id);
        }
        else{
            return $this->get_by_id($request, $id);
        }
    }

    public function get_by_id(Request $request, $id)
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
        $req['id_balita']=$id;
        $validation=Validator::make($req, [
            "id_balita" =>"required|exists:App\Models\BalitaModel"
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()->first()
            ], 500);
        }

        //SUCCESS
        $balita=BalitaRepo::get_balita($req['id_balita'], "id_balita");

        return response()->json([
            'data'  =>$balita
        ]);
    }

    public function get_by_nik(Request $request, $id)
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
        $req['nik']=$id;
        $validation=Validator::make($req, [
            "nik" =>"required|exists:App\Models\BalitaModel"
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()->first()
            ], 500);
        }

        //SUCCESS
        $balita=BalitaRepo::get_balita($req['nik'], "nik");

        return response()->json([
            'data'  =>$balita
        ]);
    }
}