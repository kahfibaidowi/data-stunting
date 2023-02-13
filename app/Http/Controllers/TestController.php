<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Stunting41182Model;
use App\Repository\SkriningBalitaRepo;

class TestController extends Controller
{


    public function update(Request $request)
    {
        DB::transaction(function(){
            $skrining=Stunting41182Model::get()->toArray();

            foreach($skrining as $val){
                //params
                $umur=$val['usia_saat_ukur'];
                $hasil_tinggi_badan_per_umur=SkriningBalitaRepo::generate_antropometri_panjang_badan_umur([
                    'jenis_kelamin' =>$val['data_anak']['jenis_kelamin'],
                    'umur'          =>$umur,
                    'tinggi_badan'  =>$val['tinggi_badan']
                ])['result']['kategori'];
                $hasil_berat_badan_per_umur=SkriningBalitaRepo::generate_antropometri_berat_badan_umur([
                    'jenis_kelamin' =>$val['data_anak']['jenis_kelamin'],
                    'umur'  =>$umur,
                    'berat_badan'  =>$val['berat_badan']
                ])['result']['kategori'];
                $hasil_berat_badan_per_tinggi_badan=SkriningBalitaRepo::generate_antropometri_berat_badan_tinggi_badan([
                    'jenis_kelamin' =>$val['data_anak']['jenis_kelamin'],
                    'umur'  =>$umur,
                    'tinggi_badan'  =>$val['tinggi_badan'],
                    'berat_badan'  =>$val['berat_badan']
                ])['result']['kategori'];

                //update
                Stunting41182Model::where("id_skrining_balita", $val['id_skrining_balita'])
                    ->update([
                        'tbu'       =>$hasil_tinggi_badan_per_umur,
                        'bbu'        =>$hasil_berat_badan_per_umur,
                        'bbtb'=>$hasil_berat_badan_per_tinggi_badan
                    ]);
            }
        });

        return response()->json([
            'status'=>"ok"
        ]);
    }

    public function get(Request $request)
    {
        echo SkriningBalitaRepo::generate_antropometri_berat_badan_umur([
            'jenis_kelamin'=>"P",
            'umur'  =>"2",
            'berat_badan'   =>null
        ])['result']['kategori'];

        $skrining=Stunting41182Model::get()->toArray();

        echo "<table cellpadding='5' style='border-collapse:collapse' border='1'>";
        echo "<tr>
                <th></th>
                <th>O BBU</th>
                <th>O TBU</th>
                <th>O BBTB</th>
                <th width='50'></th>
                <th>BBU</th>
                <th>TBU</th>
                <th>BBTB</th>
                <th width='50'></th>
                <th></th>
                <th></th>
                <th></th>
            </tr>";

        $c_bbu=0;
        $c_tbu=0;
        $c_bbtb=0;
        foreach($skrining as $val){
            $bbu="";
            if($val['hasil_berat_badan_per_umur']!=$val['bbu'] && $val['tinggi_badan']!=""){
                $bbu="false";
                $c_bbu++;
            }
            
            $tbu="";
            if($val['hasil_tinggi_badan_per_umur']!=$val['tbu'] && $val['tinggi_badan']!=""){
                $tbu="false";
                $c_tbu++;
            }
            
            $bbtb="";
            if($val['hasil_berat_badan_per_tinggi_badan']!=$val['bbtb'] && $val['tinggi_badan']!=""){
                $bbtb="false";
                $c_bbtb++;
            }

            echo "<tr>
                    <td><input type='checkbox'/></td>
                    <td>".$val['hasil_berat_badan_per_umur']."</td>
                    <td>".$val['hasil_tinggi_badan_per_umur']."</td>
                    <td>".$val['hasil_berat_badan_per_tinggi_badan']."</td>
                    <td></td>
                    <td>".$val['bbu']."</td>
                    <td>".$val['tbu']."</td>
                    <td>".$val['bbtb']."</td>
                    <td></td>
                    <td>".$bbu."</td>
                    <td>".$tbu."</td>
                    <td>".$bbtb."</td>
                </tr>";
        }
        echo "<tr><td colspan='9'></td><td>".$c_bbu."</td><td>".$c_tbu."</td><td>".$c_bbtb."</td></tr>";
        echo "</table>";
    }
}