<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\BalitaModel;
use App\Models\BalitaSkriningModel;
use App\Models\UserModel;
use App\Repository\BalitaSkriningRepo;

class BalitaSkriningController extends Controller
{

    public function add(Request $request)
    {
        $login_data=$request['fm__login_data'];
        $req=$request->all();
        $today=date("Y-m-d");

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
            'input_bulan'           =>"required|numeric",
            'id_balita'             =>[
                "required",
                Rule::exists("App\Models\BalitaModel"),
                function($attr, $value, $fail)use($req, $today){
                    $balita=BalitaModel::find($value);

                    if(is_null($balita)) return $fail("id_balita error.");
                    if(is_null($balita['tgl_lahir'])) return $fail("tgl_lahir error.");
                    if(is_null($balita['jenis_kelamin'])) return $fail("jenis_kelamin error.");
                    if(!isset($req['input_bulan'])) return $fail("input_bulan error.");

                    $umur=count_month($balita['tgl_lahir'], $today)+$req['input_bulan'];
                    $r_skrining=BalitaSkriningModel::
                        where("id_balita", $value)
                        ->where("usia_saat_ukur", $umur)
                        ->first();
                    if(!is_null($r_skrining)) return $fail("Only 1/month input skrining.");
                }
            ],
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
        DB::transaction(function()use($req, $today){
            //params
            $balita=BalitaModel::find($req['id_balita']);
            $umur=count_month($balita['tgl_lahir'], $today)+$req['input_bulan'];

            //generated
            $hasil_tinggi_badan_per_umur=BalitaSkriningRepo::generate_antropometri_panjang_badan_umur([
                'jenis_kelamin' =>$balita['jenis_kelamin'],
                'umur'          =>$umur,
                'tinggi_badan'  =>$req['tinggi_badan']
            ])['result']['kategori'];
            $hasil_berat_badan_per_umur=BalitaSkriningRepo::generate_antropometri_berat_badan_umur([
                'jenis_kelamin' =>$balita['jenis_kelamin'],
                'umur'          =>$umur,
                'berat_badan'   =>$req['berat_badan']
            ])['result']['kategori'];
            $hasil_berat_badan_per_tinggi_badan=BalitaSkriningRepo::generate_antropometri_berat_badan_tinggi_badan([
                'jenis_kelamin' =>$balita['jenis_kelamin'],
                'umur'          =>$umur,
                'tinggi_badan'  =>$req['tinggi_badan'],
                'berat_badan'   =>$req['berat_badan']
            ])['result']['kategori'];
            $hasil_status_gizi=BalitaSkriningRepo::generate_status_gizi([
                'jenis_kelamin' =>$balita['jenis_kelamin'],
                'umur'          =>$umur,
                'berat_badan'   =>$req['berat_badan'],
                'id_balita'     =>$req['id_balita']
            ]);

            //create
            BalitaSkriningModel::create([
                'id_user'       =>trim($req['id_user'])!=""?$req['id_user']:null,
                'id_balita'     =>$req['id_balita'],
                'berat_badan'   =>$req['berat_badan'],
                'tinggi_badan'  =>$req['tinggi_badan'],
                'usia_saat_ukur'=>$umur,
                'hasil_tinggi_badan_per_umur'       =>$hasil_tinggi_badan_per_umur,
                'hasil_berat_badan_per_umur'        =>$hasil_berat_badan_per_umur,
                'hasil_berat_badan_per_tinggi_badan'=>$hasil_berat_badan_per_tinggi_badan,
                'hasil_status_gizi'                 =>$hasil_status_gizi
            ]);

            //NEXT SKRINING
            $n_skrining=BalitaSkriningModel::
                where("id_balita", $req['id_balita'])
                ->where("usia_saat_ukur", $umur+1)
                ->orderByDesc("id_balita_skrining")
                ->lockForUpdate()
                ->first();
            
            if(!is_null($n_skrining)){
                //params
                $n_umur=$n_skrining['usia_saat_ukur'];
                $n_hasil_status_gizi=BalitaSkriningRepo::generate_status_gizi([
                    'jenis_kelamin' =>$balita['jenis_kelamin'],
                    'umur'          =>$n_umur,
                    'berat_badan'   =>$n_skrining['berat_badan'],
                    'id_balita'     =>$req['id_balita']
                ]);

                //update
                $n_skrining->update([
                    'hasil_status_gizi' =>$n_hasil_status_gizi
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
        $today=date("Y-m-d");

        //ROLE AUTHENTICATION
        if(!in_array($login_data['role'], ['admin', 'dinkes', 'posyandu'])){
            return response()->json([
                'error' =>"ACCESS_NOT_ALLOWED"
            ], 403);
        }

        //VALIDATION
        $req['id_balita_skrining']=$id;
        $validation=Validator::make($req, [
            'id_balita_skrining'=>[
                "required",
                Rule::exists("App\Models\BalitaSkriningModel")->where(function($q)use($req, $login_data){
                    if($login_data['role']=="posyandu"){
                        return $q->where("id_user", $login_data['id_user']);
                    }
                }),
                function($attr, $value, $fail)use($req){
                    $balita_skrining=BalitaSkriningModel::with("balita")->find($value);

                    if(is_null($balita_skrining)) return $fail("id_balita_skrining error.");
                    if(is_null($balita_skrining['balita']['tgl_lahir'])) return $fail("tgl_lahir error.");
                    if(is_null($balita_skrining['balita']['jenis_kelamin'])) return $fail("jenis_kelamin error.");
                }
            ],
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
            $skrining=BalitaSkriningModel::with("balita")
                ->where("id_balita_skrining", $req['id_balita_skrining'])
                ->lockForUpdate()
                ->first();

            //params
            $umur=$skrining['usia_saat_ukur'];
            $hasil_tinggi_badan_per_umur=BalitaSkriningRepo::generate_antropometri_panjang_badan_umur([
                'jenis_kelamin' =>$skrining['balita']['jenis_kelamin'],
                'umur'          =>$umur,
                'tinggi_badan'  =>$req['tinggi_badan']
            ])['result']['kategori'];
            $hasil_berat_badan_per_umur=BalitaSkriningRepo::generate_antropometri_berat_badan_umur([
                'jenis_kelamin' =>$skrining['balita']['jenis_kelamin'],
                'umur'          =>$umur,
                'berat_badan'   =>$req['berat_badan']
            ])['result']['kategori'];
            $hasil_berat_badan_per_tinggi_badan=BalitaSkriningRepo::generate_antropometri_berat_badan_tinggi_badan([
                'jenis_kelamin' =>$skrining['balita']['jenis_kelamin'],
                'umur'          =>$umur,
                'tinggi_badan'  =>$req['tinggi_badan'],
                'berat_badan'   =>$req['berat_badan']
            ])['result']['kategori'];
            $hasil_status_gizi=BalitaSkriningRepo::generate_status_gizi([
                'jenis_kelamin' =>$skrining['balita']['jenis_kelamin'],
                'umur'          =>$umur,
                'berat_badan'   =>$req['berat_badan'],
                'id_balita'     =>$skrining['id_balita']
            ]);

            //update
            $skrining->update([
                'berat_badan'   =>$req['berat_badan'],
                'tinggi_badan'  =>$req['tinggi_badan'],
                'hasil_tinggi_badan_per_umur'       =>$hasil_tinggi_badan_per_umur,
                'hasil_berat_badan_per_umur'        =>$hasil_berat_badan_per_umur,
                'hasil_berat_badan_per_tinggi_badan'=>$hasil_berat_badan_per_tinggi_badan,
                'hasil_status_gizi'                 =>$hasil_status_gizi
            ]);

            //NEXT SKRINING
            $n_skrining=BalitaSkriningModel::
                where("id_balita", $skrining['id_balita'])
                ->where("usia_saat_ukur", $umur+1)
                ->orderByDesc("id_balita_skrining")
                ->lockForUpdate()
                ->first();
            
            if(!is_null($n_skrining)){
                //params
                $n_umur=$n_skrining['usia_saat_ukur'];
                $n_hasil_status_gizi=BalitaSkriningRepo::generate_status_gizi([
                    'jenis_kelamin' =>$skrining['balita']['jenis_kelamin'],
                    'umur'          =>$n_umur,
                    'berat_badan'   =>$n_skrining['berat_badan'],
                    'id_balita'     =>$skrining['id_balita']
                ]);

                //update
                $n_skrining->update([
                    'hasil_status_gizi' =>$n_hasil_status_gizi
                ]);
            }
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
        if(!in_array($login_data['role'], ['admin', 'dinkes', 'posyandu'])){
            return response()->json([
                'error' =>"ACCESS_NOT_ALLOWED"
            ], 403);
        }

        //VALIDATION
        $req['id_balita_skrining']=$id;
        $validation=Validator::make($req, [
            'id_balita_skrining'=>[
                Rule::exists("App\Models\BalitaSkriningModel")->where(function($q)use($req, $login_data){
                    if($login_data['role']=="posyandu"){
                        return $q->where("id_user", $login_data['id_user']);
                    }
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
        DB::transaction(function()use($req){
            $skrining=BalitaSkriningModel::lockForUpdate()->find($req['id_balita_skrining']);

            BalitaSkriningModel::
                where("id_balita", $skrining['id_balita'])
                ->where("usia_saat_ukur", $skrining['usia_saat_ukur']+1)
                ->update([
                    'hasil_status_gizi' =>"O"
                ]);
            
            $skrining->delete();
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
        if(!in_array($login_data['role'], ['admin', 'dinkes', 'posyandu'])){
            return response()->json([
                'error' =>"ACCESS_NOT_ALLOWED"
            ], 403);
        }

        //VALIDATION
        $req['id_balita_skrining']=$id;
        $validation=Validator::make($req, [
            'id_balita_skrining'=>"required|exists:App\Models\BalitaSkriningModel,id_balita_skrining"
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()
            ], 500);
        }

        //SUCCESS
        $skrining=BalitaSkriningRepo::get_skrining($req['id_balita_skrining'], "id_balita_skrining");

        return response()->json([
            'data'  =>$skrining
        ]);
    }

    public function gets(Request $request)
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
            'per_page'  =>[
                Rule::requiredIf(!isset($req['per_page'])),
                'integer',
                'min:1'
            ],
            'q'         =>[
                Rule::requiredIf(!isset($req['q']))
            ],
            'nik'       =>[
                Rule::requiredIf(!isset($req['nik']))
            ],
            'district_id'   =>[
                Rule::requiredIf(!isset($req['district_id'])),
                Rule::exists("\App\Models\RegionModel", "id_region")->where(function($q){
                    return $q->where("type", "kecamatan");
                })
            ],
            'village_id'   =>[
                Rule::requiredIf(!isset($req['village_id'])),
                Rule::exists("\App\Models\RegionModel", "id_region")->where(function($q){
                    return $q->where("type", "desa");
                })
            ],
            'posyandu_id'   =>[
                Rule::requiredIf(!isset($req['posyandu_id'])),
                Rule::exists("App\Models\UserModel", "id_user")
            ],
            'bbu'   =>[
                Rule::requiredIf(!isset($req['bbu']))
            ],
            'tbu'   =>[
                Rule::requiredIf(!isset($req['tbu']))
            ],
            'bbtb'  =>[
                Rule::requiredIf(!isset($req['bbtb']))
            ],
            'status_gizi'   =>[
                Rule::requiredIf(!isset($req['status_gizi']))
            ],
            'tindakan'      =>[
                Rule::requiredIf(!isset($req['tindakan'])),
                "in:rujuk,tidak_ada"
            ]
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()
            ], 500);
        }

        //SUCCESS
        $skrining=BalitaSkriningRepo::gets_skrining($req);

        return response()->json([
            'first_page'    =>1,
            'current_page'  =>$skrining['current_page'],
            'last_page'     =>$skrining['last_page'],
            'data'          =>$skrining['data']
        ]);
    }

    public function get_formula(Request $request)
    {
        $login_data=$request['fm__login_data'];
        $req=$request->all();

        //ROLE AUTHENTICATION
        if(!in_array($login_data['role'], ['admin', 'dinkes', 'posyandu'])){
            return response()->json([
                'error' =>"ACCESS_NOT_ALLOWED"
            ], 403);
        }

        //SUCCESS
        return response()->json([
            'data'  =>[
                'l' =>[
                    'berat_badan_umur'  =>BalitaSkriningRepo::table_bb_u_laki_laki(),
                    'tinggi_badan_umur' =>BalitaSkriningRepo::table_pb_u_laki_laki(),
                    'bb_tb'             =>[
                        '024'   =>BalitaSkriningRepo::table_bb_tb_024_laki_laki(),
                        '2460'  =>BalitaSkriningRepo::table_bb_tb_2460_laki_laki()
                    ]
                ],
                'p' =>[
                    'berat_badan_umur'  =>BalitaSkriningRepo::table_bb_u_perempuan(),
                    'tinggi_badan_umur' =>BalitaSkriningRepo::table_pb_u_perempuan(),
                    'bb_tb'             =>[
                        '024'   =>BalitaSkriningRepo::table_bb_tb_024_perempuan(),
                        '2460'  =>BalitaSkriningRepo::table_bb_tb_2460_perempuan()
                    ]
                ]
            ]
        ]);
    }
}