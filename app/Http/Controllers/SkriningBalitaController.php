<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\SkriningBalitaModel;
use App\Models\UserModel;
use App\Repository\SkriningBalitaRepo;

class SkriningBalitaController extends Controller
{

    public function add(Request $request)
    {
        $login_data=$request['fm__login_data'];
        $req=$request->all();

        //ROLE AUTHENTICATION
        if(!in_array($login_data['role'], ['admin', 'dinkes', 'posyandu'])){
            return response()->json([
                'error' =>"ACCESS_NOT_ALLOWED"
            ], 403);
        }

        //VALIDATION
        $validation=Validator::make($req, [
            'id_user'       =>[
                Rule::requiredIf(function()use($req, $login_data){
                    if(!isset($req['id_user'])) return true;
                    if($login_data['role']=="posyandu" && trim($req['id_user'])=="") return true;
                    return false;
                }),
                function($attr, $value, $fail)use($req, $login_data){
                    if($login_data['role']=="posyandu"){
                        if($login_data['id_user']!=$req['id_user']){
                            return $fail("Id user error.");
                        }
                    }
                    return true;
                },
                Rule::exists("App\Models\UserModel", "id_user")
            ],
            'data_anak'             =>"required",
            'data_anak.nik'         =>"required",
            'data_anak.tgl_lahir'   =>"required|date_format:Y-m-d",
            'data_anak.jenis_kelamin'=>"required|in:L,P",
            'data_anak.ibu'         =>[
                Rule::requiredIf(!isset($req['data_anak']['ibu']))
            ],
            'data_anak.ayah'        =>[
                Rule::requiredIf(!isset($req['data_anak']['ayah']))
            ],
            'berat_badan_lahir' =>"required|numeric",
            'tinggi_badan_lahir'=>"required|numeric",
            'berat_badan'   =>"required|numeric",
            'tinggi_badan'  =>"required|numeric"
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()->first()
            ], 500);
        }

        //SUCCESS
        DB::transaction(function()use($req){
            //params
            $umur=count_month($req['data_anak']['tgl_lahir'], date("Y-m-d"));
            $hasil_tinggi_badan_per_umur=SkriningBalitaRepo::generate_antropometri_panjang_badan_umur([
                'jenis_kelamin' =>"L",
                'umur'          =>$umur,
                'tinggi_badan'   =>$req['tinggi_badan']
            ])['result']['kategori'];
            $hasil_berat_badan_per_umur=SkriningBalitaRepo::generate_antropometri_berat_badan_umur([
                'jenis_kelamin' =>"L",
                'umur'  =>$umur,
                'berat_badan'  =>$req['berat_badan']
            ])['result']['kategori'];
            $hasil_berat_badan_per_tinggi_badan=SkriningBalitaRepo::generate_antropometri_berat_badan_tinggi_badan([
                'jenis_kelamin' =>"L",
                'umur'  =>$umur,
                'tinggi_badan'  =>$req['tinggi_badan'],
                'berat_badan'  =>$req['berat_badan']
            ])['result']['kategori'];

            //create
            SkriningBalitaModel::create([
                'id_user'   =>trim($req['id_user'])!=""?$req['id_user']:null,
                'data_anak' =>$req['data_anak'],
                'berat_badan_lahir' =>$req['berat_badan_lahir'],
                'tinggi_badan_lahir'=>$req['tinggi_badan_lahir'],
                'berat_badan'   =>$req['berat_badan'],
                'tinggi_badan'  =>$req['tinggi_badan'],
                'usia_saat_ukur'=>$umur,
                'hasil_tinggi_badan_per_umur'       =>$hasil_tinggi_badan_per_umur,
                'hasil_berat_badan_per_umur'        =>$hasil_berat_badan_per_umur,
                'hasil_berat_badan_per_tinggi_badan'=>$hasil_berat_badan_per_tinggi_badan
            ]);
        });

        return response()->json([
            'status'=>"ok"
        ]);
    }

    public function add_multiple(Request $request)
    {
        $login_data=$request['fm__login_data'];
        $req=$request->all();

        //ROLE AUTHENTICATION
        if(!in_array($login_data['role'], ['admin', 'dinkes', 'posyandu'])){
            return response()->json([
                'error' =>"ACCESS_NOT_ALLOWED"
            ], 403);
        }

        //VALIDATION
        $validation=Validator::make($req, [
            'id_user'       =>[
                Rule::requiredIf(function()use($req, $login_data){
                    if(!isset($req['id_user'])) return true;
                    if($login_data['role']=="posyandu" && trim($req['id_user'])=="") return true;
                    return false;
                }),
                function($attr, $value, $fail)use($req, $login_data){
                    if($login_data['role']=="posyandu"){
                        if($login_data['id_user']!=$req['id_user']){
                            return $fail("Id user error.");
                        }
                    }
                    return true;
                },
                Rule::exists("App\Models\UserModel", "id_user")
            ],
            'skrining'              =>"required|array",
            'skrining.*.data_anak'             =>"required|required_array_keys:ayah",
            'skrining.*.data_anak.nik'         =>"required",
            'skrining.*.data_anak.tgl_lahir'   =>"required|date_format:Y-m-d",
            'skrining.*.data_anak.jenis_kelamin'=>"required|in:L,P",
            'skrining.*.berat_badan_lahir' =>"required|numeric",
            'skrining.*.tinggi_badan_lahir'=>"required|numeric",
            'skrining.*.berat_badan'   =>"required|numeric",
            'skrining.*.tinggi_badan'  =>"required|numeric"
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
                $umur=count_month($val['data_anak']['tgl_lahir'], $date);
                $hasil_tinggi_badan_per_umur=SkriningBalitaRepo::generate_antropometri_panjang_badan_umur([
                    'jenis_kelamin' =>"L",
                    'umur'          =>$umur,
                    'tinggi_badan'   =>$val['tinggi_badan']
                ])['result']['kategori'];
                $hasil_berat_badan_per_umur=SkriningBalitaRepo::generate_antropometri_berat_badan_umur([
                    'jenis_kelamin' =>"L",
                    'umur'  =>$umur,
                    'berat_badan'  =>$val['berat_badan']
                ])['result']['kategori'];
                $hasil_berat_badan_per_tinggi_badan=SkriningBalitaRepo::generate_antropometri_berat_badan_tinggi_badan([
                    'jenis_kelamin' =>"L",
                    'umur'  =>$umur,
                    'tinggi_badan'  =>$val['tinggi_badan'],
                    'berat_badan'  =>$val['berat_badan']
                ])['result']['kategori'];

                //update
                SkriningBalitaModel::create([
                    'id_user'   =>trim($req['id_user'])!=""?$req['id_user']:null,
                    'data_anak' =>$val['data_anak'],
                    'berat_badan_lahir' =>$val['berat_badan_lahir'],
                    'tinggi_badan_lahir'=>$val['tinggi_badan_lahir'],
                    'berat_badan'   =>$val['berat_badan'],
                    'tinggi_badan'  =>$val['tinggi_badan'],
                    'usia_saat_ukur'=>$umur,
                    'hasil_tinggi_badan_per_umur'       =>$hasil_tinggi_badan_per_umur,
                    'hasil_berat_badan_per_umur'        =>$hasil_berat_badan_per_umur,
                    'hasil_berat_badan_per_tinggi_badan'=>$hasil_berat_badan_per_tinggi_badan
                ]);
            }
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
        if(!in_array($login_data['role'], ['admin', 'dinkes', 'posyandu'])){
            return response()->json([
                'error' =>"ACCESS_NOT_ALLOWED"
            ], 403);
        }

        //VALIDATION
        $req['id_skrining_balita']=$id;
        $validation=Validator::make($req, [
            'id_skrining_balita'=>"required|exists:App\Models\SkriningBalitaModel,id_skrining_balita",
            'berat_badan_lahir' =>"required|numeric",
            'tinggi_badan_lahir'=>"required|numeric",
            'berat_badan'   =>"required|numeric",
            'tinggi_badan'  =>"required|numeric"
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()->first()
            ], 500);
        }

        //SUCCESS
        DB::transaction(function()use($req){
            $skrining=SkriningBalitaRepo::get_skrining($req['id_skrining_balita']);

            //params
            $umur=$skrining['usia_saat_ukur'];
            $hasil_tinggi_badan_per_umur=SkriningBalitaRepo::generate_antropometri_panjang_badan_umur([
                'jenis_kelamin' =>"L",
                'umur'  =>$umur,
                'tinggi_badan'   =>$req['tinggi_badan']
            ])['result']['kategori'];
            $hasil_berat_badan_per_umur=SkriningBalitaRepo::generate_antropometri_berat_badan_umur([
                'jenis_kelamin' =>"L",
                'umur'  =>$umur,
                'berat_badan'  =>$req['berat_badan']
            ])['result']['kategori'];
            $hasil_berat_badan_per_umur=SkriningBalitaRepo::generate_antropometri_berat_badan_tinggi_badan([
                'jenis_kelamin' =>"L",
                'umur'  =>$umur,
                'tinggi_badan'  =>$req['tinggi_badan'],
                'berat_badan'  =>$req['berat_badan']
            ])['result']['kategori'];

            //update
            SkriningBalitaModel::where("id_skrining_balita", $req['id_skrining_balita'])
                ->update([
                    'berat_badan_lahir' =>$req['berat_badan_lahir'],
                    'tinggi_badan_lahir'=>$req['tinggi_badan_lahir'],
                    'berat_badan'   =>$req['berat_badan'],
                    'tinggi_badan'  =>$req['tinggi_badan'],
                    'hasil_tinggi_badan_per_umur'       =>$hasil_tinggi_badan_per_umur,
                    'hasil_berat_badan_per_umur'        =>$hasil_berat_badan_per_umur,
                    'hasil_berat_badan_per_tinggi_badan'=>$hasil_berat_badan_per_tinggi_badan
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
        $req['id_skrining_balita']=$id;
        $validation=Validator::make($req, [
            'id_skrining_balita'=>"required|exists:App\Models\SkriningBalitaModel,id_skrining_balita"
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()
            ], 500);
        }

        //SUCCESS
        DB::transaction(function()use($req){
            SkriningBalitaModel::where("id_skrining_balita", $req['id_skrining_balita'])->delete();
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

    private function get_by_id(Request $request, $id)
    {
        $req=$request->all();

        //ROLE AUTHENTICATION
        if(false){
            return response()->json([
                'error' =>"ACCESS_NOT_ALLOWED"
            ], 403);
        }

        //VALIDATION
        $req['id_skrining_balita']=$id;
        $validation=Validator::make($req, [
            'id_skrining_balita'=>"required|exists:App\Models\SkriningBalitaModel,id_skrining_balita"
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()
            ], 500);
        }

        //SUCCESS
        $skrining=SkriningBalitaRepo::get_skrining($req['id_skrining_balita'], "id_skrining_balita");

        return response()->json([
            'data'  =>$skrining
        ]);
    }

    private function get_by_nik(Request $request, $id)
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
        $req['nik']=$id;
        $validation=Validator::make($req, [
            'nik'=>[
                "required",
                function($attr, $value, $fail)use($req){
                    $v=SkriningBalitaModel::where("data_anak->nik", $value)->first();

                    if(is_null($v)) return $fail("Nik not found.");
                    return true;
                }
            ]
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()
            ], 500);
        }

        //SUCCESS
        $skrining=SkriningBalitaRepo::get_skrining($req['nik'], "data_anak->nik");

        return response()->json([
            'data'  =>$skrining
        ]);
    }

    public function gets(Request $request)
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
        $skrining=SkriningBalitaRepo::gets_skrining($req);

        return response()->json([
            'first_page'    =>1,
            'current_page'  =>$skrining['current_page'],
            'last_page'     =>$skrining['last_page'],
            'data'          =>$skrining['data']
        ]);
    }
}