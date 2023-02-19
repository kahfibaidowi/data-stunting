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
            'data_anak'             =>"required",
            'data_anak.nik'         =>[
                "required",
                function($attr, $value, $fail)use($req, $today){
                    if(!isset($req['data_anak']['tgl_lahir'])) return $fail("nik error.");
                    if(!isset($req['input_bulan'])) return $fail("input_bulan error.");

                    $umur=count_month($req['data_anak']['tgl_lahir'], $today)+$req['input_bulan'];
                    $r_skrining=SkriningBalitaModel::
                        where("data_anak->nik", $value)
                        ->where("usia_saat_ukur", $umur)
                        ->first();
                    if(!is_null($r_skrining)) return $fail("Only 1/month input skrining.");
                }
            ],
            'data_anak.no_kk'       =>"required",
            'data_anak.tgl_lahir'   =>"required|date_format:Y-m-d",
            'data_anak.jenis_kelamin'=>"required|in:L,P",
            'data_anak.ibu'         =>"required",
            'data_anak.ibu.nik'     =>"required",
            'data_anak.ibu.nama_lengkap'=>"required",
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
        DB::transaction(function()use($req, $today){
            //params
            $umur=count_month($req['data_anak']['tgl_lahir'], $today)+$req['input_bulan'];
            $hasil_tinggi_badan_per_umur=SkriningBalitaRepo::generate_antropometri_panjang_badan_umur([
                'jenis_kelamin' =>$req['data_anak']['jenis_kelamin'],
                'umur'          =>$umur,
                'tinggi_badan'  =>$req['tinggi_badan']
            ])['result']['kategori'];
            $hasil_berat_badan_per_umur=SkriningBalitaRepo::generate_antropometri_berat_badan_umur([
                'jenis_kelamin' =>$req['data_anak']['jenis_kelamin'],
                'umur'          =>$umur,
                'berat_badan'   =>$req['berat_badan']
            ])['result']['kategori'];
            $hasil_berat_badan_per_tinggi_badan=SkriningBalitaRepo::generate_antropometri_berat_badan_tinggi_badan([
                'jenis_kelamin' =>$req['data_anak']['jenis_kelamin'],
                'umur'          =>$umur,
                'tinggi_badan'  =>$req['tinggi_badan'],
                'berat_badan'   =>$req['berat_badan']
            ])['result']['kategori'];
            $hasil_status_gizi=SkriningBalitaRepo::generate_status_gizi([
                'jenis_kelamin' =>$req['data_anak']['jenis_kelamin'],
                'umur'          =>$umur,
                'berat_badan'   =>$req['berat_badan'],
                'nik'           =>$req['data_anak']['nik']
            ]);

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
                'hasil_berat_badan_per_tinggi_badan'=>$hasil_berat_badan_per_tinggi_badan,
                'hasil_status_gizi'                 =>$hasil_status_gizi
            ]);

            //NEXT SKRINING
            $n_skrining=SkriningBalitaModel::
                where("data_anak->nik", $req['data_anak']['nik'])
                ->where("usia_saat_ukur", $umur+1)
                ->orderBy("id_skrining_balita")
                ->lockForUpdate()
                ->first();
            
            if(!is_null($n_skrining)){
                //params
                $n_umur=$n_skrining['usia_saat_ukur'];
                $n_hasil_status_gizi=SkriningBalitaRepo::generate_status_gizi([
                    'jenis_kelamin' =>$n_skrining['data_anak']['jenis_kelamin'],
                    'umur'          =>$n_umur,
                    'berat_badan'   =>$n_skrining['berat_badan'],
                    'nik'           =>$n_skrining['data_anak']['nik']
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
        $req['id_skrining_balita']=$id;
        $validation=Validator::make($req, [
            'id_skrining_balita'=>[
                "required",
                Rule::exists("App\Models\SkriningBalitaModel")->where(function($q)use($req, $login_data){
                    if($login_data['role']=="posyandu"){
                        return $q->where("id_user", $login_data['id_user']);
                    }
                })
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
            $skrining=SkriningBalitaModel::where("id_skrining_balita", $req['id_skrining_balita'])->lockForUpdate()->first();

            //params
            $umur=$skrining['usia_saat_ukur'];
            $hasil_tinggi_badan_per_umur=SkriningBalitaRepo::generate_antropometri_panjang_badan_umur([
                'jenis_kelamin' =>$skrining['data_anak']['jenis_kelamin'],
                'umur'          =>$umur,
                'tinggi_badan'  =>$req['tinggi_badan']
            ])['result']['kategori'];
            $hasil_berat_badan_per_umur=SkriningBalitaRepo::generate_antropometri_berat_badan_umur([
                'jenis_kelamin' =>$skrining['data_anak']['jenis_kelamin'],
                'umur'          =>$umur,
                'berat_badan'   =>$req['berat_badan']
            ])['result']['kategori'];
            $hasil_berat_badan_per_tinggi_badan=SkriningBalitaRepo::generate_antropometri_berat_badan_tinggi_badan([
                'jenis_kelamin' =>$skrining['data_anak']['jenis_kelamin'],
                'umur'          =>$umur,
                'tinggi_badan'  =>$req['tinggi_badan'],
                'berat_badan'   =>$req['berat_badan']
            ])['result']['kategori'];
            $hasil_status_gizi=SkriningBalitaRepo::generate_status_gizi([
                'jenis_kelamin' =>$skrining['data_anak']['jenis_kelamin'],
                'umur'          =>$umur,
                'berat_badan'   =>$req['berat_badan'],
                'nik'           =>$skrining['data_anak']['nik']
            ]);

            //update
            SkriningBalitaModel::where("id_skrining_balita", $req['id_skrining_balita'])
                ->update([
                    'berat_badan_lahir' =>$req['berat_badan_lahir'],
                    'tinggi_badan_lahir'=>$req['tinggi_badan_lahir'],
                    'berat_badan'   =>$req['berat_badan'],
                    'tinggi_badan'  =>$req['tinggi_badan'],
                    'hasil_tinggi_badan_per_umur'       =>$hasil_tinggi_badan_per_umur,
                    'hasil_berat_badan_per_umur'        =>$hasil_berat_badan_per_umur,
                    'hasil_berat_badan_per_tinggi_badan'=>$hasil_berat_badan_per_tinggi_badan,
                    'hasil_status_gizi'                 =>$hasil_status_gizi
                ]);

            //NEXT SKRINING
            $n_skrining=SkriningBalitaModel::
                where("data_anak->nik", $skrining['data_anak']['nik'])
                ->where("usia_saat_ukur", $umur+1)
                ->orderBy("id_skrining_balita")
                ->lockForUpdate()
                ->first();
            
            if(!is_null($n_skrining)){
                //params
                $n_umur=$n_skrining['usia_saat_ukur'];
                $n_hasil_status_gizi=SkriningBalitaRepo::generate_status_gizi([
                    'jenis_kelamin' =>$n_skrining['data_anak']['jenis_kelamin'],
                    'umur'          =>$n_umur,
                    'berat_badan'   =>$n_skrining['berat_badan'],
                    'nik'           =>$n_skrining['data_anak']['nik']
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
        $req['id_skrining_balita']=$id;
        $validation=Validator::make($req, [
            'id_skrining_balita'=>[
                "required",
                Rule::exists("App\Models\SkriningBalitaModel")->where(function($q)use($req, $login_data){
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
            $skrining=SkriningBalitaModel::where("id_skrining_balita", $req['id_skrining_balita'])
                ->lockForUpdate()
                ->first();

            SkriningBalitaModel::
                where("data_anak->nik", $skrining['data_anak']['nik'])
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
        if(!in_array($login_data['role'], ['admin', 'dinkes', 'posyandu'])){
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
        if(!in_array($login_data['role'], ['admin', 'dinkes', 'posyandu'])){
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
            ],
            'umur_start'    =>[
                Rule::requiredIf(function()use($req){
                    if(!isset($req['umur_start'])) return true;
                    if(!isset($req['umur_end'])) return true;
                    if(trim($req['umur_end'])!="") return true;
                }),
                "integer",
                "min:0",
                "max:60"
            ],
            'umur_end'    =>[
                Rule::requiredIf(function()use($req){
                    if(!isset($req['umur_end'])) return true;
                    if(!isset($req['umur_start'])) return true;
                    if(trim($req['umur_start'])!="") return true;
                }),
                "integer",
                "min:0",
                "max:60"
            ],
            'hide_tb_0' =>"nullable|in:y,n",
            'hide_bb_0' =>"nullable|in:y,n"
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
    
    public function gets_group_nik(Request $request)
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
            ],
            'umur_start'    =>[
                Rule::requiredIf(function()use($req){
                    if(!isset($req['umur_start'])) return true;
                    if(!isset($req['umur_end'])) return true;
                    if(trim($req['umur_end'])!="") return true;
                }),
                "integer",
                "min:0",
                "max:60"
            ],
            'umur_end'    =>[
                Rule::requiredIf(function()use($req){
                    if(!isset($req['umur_end'])) return true;
                    if(!isset($req['umur_start'])) return true;
                    if(trim($req['umur_start'])!="") return true;
                }),
                "integer",
                "min:0",
                "max:60"
            ],
            'hide_tb_0' =>"nullable|in:y,n",
            'hide_bb_0' =>"nullable|in:y,n"
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()
            ], 500);
        }

        //SUCCESS
        $skrining=SkriningBalitaRepo::gets_skrining_group_nik($req);

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
                    'berat_badan_umur'  =>SkriningBalitaRepo::table_bb_u_laki_laki(),
                    'tinggi_badan_umur' =>SkriningBalitaRepo::table_pb_u_laki_laki(),
                    'bb_tb'             =>[
                        '024'   =>SkriningBalitaRepo::table_bb_tb_024_laki_laki(),
                        '2460'  =>SkriningBalitaRepo::table_bb_tb_2460_laki_laki()
                    ]
                ],
                'p' =>[
                    'berat_badan_umur'  =>SkriningBalitaRepo::table_bb_u_perempuan(),
                    'tinggi_badan_umur' =>SkriningBalitaRepo::table_pb_u_perempuan(),
                    'bb_tb'             =>[
                        '024'   =>SkriningBalitaRepo::table_bb_tb_024_perempuan(),
                        '2460'  =>SkriningBalitaRepo::table_bb_tb_2460_perempuan()
                    ]
                ]
            ]
        ]);
    }
}