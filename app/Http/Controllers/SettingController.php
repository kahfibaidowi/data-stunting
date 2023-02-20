<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\SkriningBalitaModel;
use App\Repository\SkriningBalitaRepo;

class SettingController extends Controller
{

    // #mengubah bb/tb yang hasilnya unknown
    public function update_bbtb_unknown(Request $request){
        $login_data=$request['fm__login_data'];
        $req=$request->all();

        //ROLE AUTHENTICATION
        if(!in_array($login_data['role'], ['admin'])){
            return response()->json([
                'error' =>"ACCESS_NOT_ALLOWED"
            ], 403);
        }

        //VALIDATION
        $validation=Validator::make($req, [
            'predict_count' =>"nullable|integer|min:0"
        ]);
        if($validation->fails()){
            return response()->json([
                'error' =>"VALIDATION_ERROR",
                'data'  =>$validation->errors()->first()
            ], 500);
        }

        //SUCCESS
        $req['predict_count']=isset($req['predict_count'])?trim($req['predict_count']):"";

        DB::transaction(function()use($req){
            $query_skrining=SkriningBalitaModel::where("hasil_berat_badan_per_tinggi_badan", "unknown")
                ->limit($req['predict_count'])
                ->lockForUpdate()
                ->get();
            
            foreach($query_skrining as $val){
                $umur=$val['usia_saat_ukur'];
                $hasil_berat_badan_per_tinggi_badan=SkriningBalitaRepo::generate_antropometri_berat_badan_tinggi_badan([
                    'jenis_kelamin' =>$val['data_anak']['jenis_kelamin'],
                    'umur'          =>$umur,
                    'tinggi_badan'  =>$val['tinggi_badan'],
                    'berat_badan'   =>$val['berat_badan']
                ])['result']['kategori'];

                $val->update([
                    'hasil_berat_badan_per_tinggi_badan'    =>$hasil_berat_badan_per_tinggi_badan
                ]);
            }
        });
            
        return response()->json([
            'status'=>"ok"
        ]);
    }
    // #melihat jumlah bb/tb yang hasilnya unknown
    public function get_count_bbtb_unknown(Request $request){
        $login_data=$request['fm__login_data'];
        $req=$request->all();

        //ROLE AUTHENTICATION
        if(!in_array($login_data['role'], ['admin'])){
            return response()->json([
                'error' =>"ACCESS_NOT_ALLOWED"
            ], 403);
        }

        //SUCCESS
        $query_count=SkriningBalitaModel::where("hasil_berat_badan_per_tinggi_badan", "unknown")
            ->get()
            ->count();
        
        return response()->json([
            'data'  =>$query_count
        ]);
    }
}