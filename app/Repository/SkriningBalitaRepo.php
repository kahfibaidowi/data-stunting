<?php

namespace App\Repository;

use App\Models\SkriningBalitaModel;
use App\Models\RegionModel;
use App\Models\UserModel;


class SkriningBalitaRepo{
    
    public static function gets_skrining($params)
    {
        //params
        $params['per_page']=trim($params['per_page']);
        $params['posyandu_id']=trim($params['posyandu_id']);
        $params['district_id']=trim($params['district_id']);
        $params['village_id']=trim($params['village_id']);
        $params['nik']=trim($params['nik']);
        $params['bbu']=trim($params['bbu']);
        $params['tbu']=trim($params['tbu']);
        $params['bbtb']=trim($params['bbtb']);
        $params['status_gizi']=trim($params['status_gizi']);
        $params['tindakan']=trim($params['tindakan']);
        $params['umur_start']=trim($params['umur_start']);
        $params['umur_end']=trim($params['umur_end']);
        $params['hide_tb_0']=isset($params['hide_tb_0'])?trim($params['hide_tb_0']):"";
        $params['hide_bb_0']=isset($params['hide_bb_0'])?trim($params['hide_bb_0']):"";

        //query
        $query=SkriningBalitaModel::with("user_posyandu", "user_posyandu.region", "user_posyandu.region.parent");
        //--kecamatan
        if($params['district_id']!=""){
            $query=$query->whereHas("user_posyandu.region.parent", function($q)use($params){
                return $q->where("id_region", $params['district_id']);
            });
        }
        //--desa
        if($params['village_id']!=""){
            $query=$query->whereHas("user_posyandu.region", function($q)use($params){
                return $q->where("id_region", $params['village_id']);
            });
        }
        //--posyandu id
        if($params['posyandu_id']!=""){
            $query=$query->where("id_user", $params['posyandu_id']);
        }
        //--bbu
        if($params['bbu']!=""){
            $query=$query->where("hasil_berat_badan_per_umur", $params['bbu']);
        }
        //--tbu
        if($params['tbu']!=""){
            $query=$query->where("hasil_tinggi_badan_per_umur", $params['tbu']);
        }
        //--bbtb
        if($params['bbtb']!=""){
            $query=$query->where("hasil_berat_badan_per_tinggi_badan", $params['bbtb']);
        }
        //--status gizi
        if($params['status_gizi']!=""){
            $query=$query->where("hasil_status_gizi", $params['status_gizi']);
        }
        //--nik
        if($params['nik']!=""){
            $query=$query->where("data_anak->nik", $params['nik']);
        }
        //--tindakan
        if($params['tindakan']!=""){
            if($params['tindakan']=="rujuk"){
                $query=$query->where(function($q){
                    $q->where("hasil_berat_badan_per_umur", "!=", "gizi_baik")
                        ->orWhere("hasil_status_gizi", "T");
                });
            }
            elseif($params['tindakan']=="tidak_ada"){
                $query=$query->where(function($q){
                    $q->where("hasil_berat_badan_per_umur", "gizi_baik")
                        ->where("hasil_status_gizi", "!=", "T");
                });
            }
        }
        //--umur start/end
        if($params['umur_start']!="" || $params['umur_end']!=""){
            $query=$query->whereBetween("usia_saat_ukur", [$params['umur_start'], $params['umur_end']]);
        }
        //-hide bb=0, tb=0
        if($params['hide_bb_0']=="y"){
            $query=$query->where("berat_badan", "!=", 0);
        }
        if($params['hide_tb_0']=="y"){
            $query=$query->where("tinggi_badan", "!=", 0);
        }
        //--q
        $query=$query->where("data_anak->nama_lengkap", "like", "%".$params['q']."%");
        //--order
        $query=$query->orderByDesc("id_skrining_balita");

        //data
        $data=$query->paginate($params['per_page'])->toArray();
        $new_data=[];
        foreach($data['data'] as $val){
            if($val['user_posyandu']!=""){
                $new_data[]=array_merge($val, [
                    'user_posyandu' =>array_merge_without($val['user_posyandu'], ['region'], [
                        'desa'      =>$val['user_posyandu']['region']['region'],
                        'kecamatan' =>$val['user_posyandu']['region']['parent']['region']
                    ])
                ]);
            }
            else{
                $new_data[]=array_merge($val, [
                    'user_posyandu' =>[
                        'desa'      =>null,
                        'kecamatan' =>null,
                        'nama_lengkap'  =>""
                    ]
                ]);
            }
        }

        return array_merge($data, [
            'data'  =>$new_data
        ]);
    }

    public static function gets_skrining_group_nik($params)
    {
        //params
        $params['per_page']=trim($params['per_page']);
        $params['posyandu_id']=trim($params['posyandu_id']);
        $params['district_id']=trim($params['district_id']);
        $params['village_id']=trim($params['village_id']);
        $params['bbu']=trim($params['bbu']);
        $params['tbu']=trim($params['tbu']);
        $params['bbtb']=trim($params['bbtb']);
        $params['status_gizi']=trim($params['status_gizi']);
        $params['tindakan']=trim($params['tindakan']);
        $params['umur_start']=trim($params['umur_start']);
        $params['umur_end']=trim($params['umur_end']);
        $params['hide_tb_0']=isset($params['hide_tb_0'])?trim($params['hide_tb_0']):"";
        $params['hide_bb_0']=isset($params['hide_bb_0'])?trim($params['hide_bb_0']):"";

        //QUERY DESA, KECAMATAN WHERE PARAMS NOT EMPTY
        if($params['village_id']!=""){
            $r_region=RegionModel::with("posyandu")
                ->where("id_region", $params['village_id'])
                ->first();

            $user_posyandu=[-1];
            foreach($r_region['posyandu'] as $posy){
                $user_posyandu[]=$posy['id_user'];
            }
        }
        elseif($params['district_id']!=""){
            $r_region=RegionModel::with("posyandu_kecamatan")
                ->where("id_region", $params['district_id'])
                ->first();

            $user_posyandu=[-1];
            foreach($r_region['posyandu_kecamatan'] as $posy){
                $user_posyandu[]=$posy['id_user'];
            }
        }

        //QUERY SKRINING
        $query=SkriningBalitaModel::selectRaw("
            max(usia_saat_ukur) as usia_saat_ukur,
            SUBSTRING(max(CONCAT(LPAD(usia_saat_ukur, 11, '0'), COALESCE(`id_skrining_balita`, ''))), 12) as id_skrining_balita, 
            SUBSTRING(max(CONCAT(LPAD(usia_saat_ukur, 11, '0'), COALESCE(`id_user`, ''))), 12) as id_user, 
            SUBSTRING(max(CONCAT(LPAD(usia_saat_ukur, 11, '0'), data_anak)), 12) as data_anak, 
            SUBSTRING(max(CONCAT(LPAD(usia_saat_ukur, 11, '0'), hasil_tinggi_badan_per_umur)), 12) as hasil_tinggi_badan_per_umur,
            SUBSTRING(max(CONCAT(LPAD(usia_saat_ukur, 11, '0'), hasil_berat_badan_per_umur)), 12) as hasil_berat_badan_per_umur,
            SUBSTRING(max(CONCAT(LPAD(usia_saat_ukur, 11, '0'), hasil_berat_badan_per_tinggi_badan)), 12) as hasil_berat_badan_per_tinggi_badan,
            SUBSTRING(max(CONCAT(LPAD(usia_saat_ukur, 11, '0'), hasil_status_gizi)), 12) as hasil_status_gizi,
            SUBSTRING(max(CONCAT(LPAD(usia_saat_ukur, 11, '0'), tinggi_badan)), 12) as tinggi_badan,
            SUBSTRING(max(CONCAT(LPAD(usia_saat_ukur, 11, '0'), berat_badan)), 12) as berat_badan,
            SUBSTRING(max(CONCAT(LPAD(usia_saat_ukur, 11, '0'), berat_badan_lahir)), 12) as berat_badan_lahir,
            SUBSTRING(max(CONCAT(LPAD(usia_saat_ukur, 11, '0'), tinggi_badan_lahir)), 12) as tinggi_badan_lahir,
            SUBSTRING(max(CONCAT(LPAD(usia_saat_ukur, 11, '0'), created_at)), 12) as created_at
        ");
        $query=$query->with("user_posyandu", "user_posyandu.region", "user_posyandu.region.parent");
        $query=$query->groupBy(
            \DB::raw("json_unquote(json_extract(`data_anak`, '$.nik'))")
        );
        //--desa, kecamatan
        if($params['village_id']!="" || $params['district_id']!=""){
            $having_user_posyandu="(";
            foreach($user_posyandu as $val){
                if($val==-1) $having_user_posyandu.=$val;
                else $having_user_posyandu.=",".$val;
            }
            $having_user_posyandu.=")";

            $query=$query->havingRaw("id_user in ".$having_user_posyandu);
        }
        //--posyandu id
        if($params['posyandu_id']!=""){
            $query=$query->having("id_user", $params['posyandu_id']);
        }
        //--bbu
        if($params['bbu']!=""){
            $query=$query->having("hasil_berat_badan_per_umur", $params['bbu']);
        }
        //--tbu
        if($params['tbu']!=""){
            $query=$query->having("hasil_tinggi_badan_per_umur", $params['tbu']);
        }
        //--bbtb
        if($params['bbtb']!=""){
            $query=$query->having("hasil_berat_badan_per_tinggi_badan", $params['bbtb']);
        }
        //--status gizi
        if($params['status_gizi']!=""){
            $query=$query->having("hasil_status_gizi", $params['status_gizi']);
        }
        //--tindakan
        if($params['tindakan']!=""){
            if($params['tindakan']=="rujuk"){
                $query=$query->having(function($q){
                    $q->having("hasil_berat_badan_per_umur", "!=", "gizi_baik")
                        ->orHaving("hasil_status_gizi", "T");
                });
            }
            elseif($params['tindakan']=="tidak_ada"){
                $query=$query->having(function($q){
                    $q->having("hasil_berat_badan_per_umur", "gizi_baik")
                        ->having("hasil_status_gizi", "!=", "T");
                });
            }
        }
        //--umur start/end
        if($params['umur_start']!="" || $params['umur_end']!=""){
            $query=$query->having(function($q)use($params){
                $q->havingRaw("usia_saat_ukur >= ".$params['umur_start']." and usia_saat_ukur <= ".$params['umur_end']);
            });
        }
        //-hide bb=0, tb=0
        if($params['hide_bb_0']=="y"){
            $query=$query->having("berat_badan", "!=", 0);
        }
        if($params['hide_tb_0']=="y"){
            $query=$query->having("tinggi_badan", "!=", 0);
        }
        //--q
        $query=$query->having("data_anak->nama_lengkap", "like", "%".$params['q']."%");
        //--order
        $query=$query->orderByDesc("id_skrining_balita");

        //DATA
        $data=$query->paginate($params['per_page'])->toArray();
        $new_data=[];
        foreach($data['data'] as $val){
            if($val['user_posyandu']!=""){
                $new_data[]=array_merge($val, [
                    'user_posyandu' =>array_merge_without($val['user_posyandu'], ['region'], [
                        'desa'      =>$val['user_posyandu']['region']['region'],
                        'kecamatan' =>$val['user_posyandu']['region']['parent']['region']
                    ])
                ]);
            }
            else{
                $new_data[]=array_merge($val, [
                    'user_posyandu' =>[
                        'desa'      =>null,
                        'kecamatan' =>null,
                        'nama_lengkap'  =>""
                    ]
                ]);
            }
        }

        return array_merge($data, [
            'data'  =>$new_data
        ]);
    }

    public static function get_skrining($skrining_id, $column)
    {
        //query
        $query=SkriningBalitaModel::where($column, $skrining_id);
        $query=$query->orderByDesc("id_skrining_balita");

        //return
        return optional($query->first())->toArray();
    }

    public static function table_bb_u_laki_laki()
    {
        $data=json_decode('[{"umur":0,"min3sd":2.1,"min2sd":2.5,"min1sd":2.9,"median":3.3,"1sd":3.9,"2sd":4.4,"3sd":5},{"umur":1,"min3sd":2.9,"min2sd":3.4,"min1sd":3.9,"median":4.5,"1sd":5.1,"2sd":5.8,"3sd":6.6},{"umur":2,"min3sd":3.8,"min2sd":4.3,"min1sd":4.9,"median":5.6,"1sd":6.3,"2sd":7.1,"3sd":8},{"umur":3,"min3sd":4.4,"min2sd":5,"min1sd":5.7,"median":6.4,"1sd":7.2,"2sd":8,"3sd":9},{"umur":4,"min3sd":4.9,"min2sd":5.6,"min1sd":6.2,"median":7,"1sd":7.8,"2sd":8.7,"3sd":9.7},{"umur":5,"min3sd":5.3,"min2sd":6,"min1sd":6.7,"median":7.5,"1sd":8.4,"2sd":9.3,"3sd":10.4},{"umur":6,"min3sd":5.7,"min2sd":6.4,"min1sd":7.1,"median":7.9,"1sd":8.8,"2sd":9.8,"3sd":10.9},{"umur":7,"min3sd":5.9,"min2sd":6.7,"min1sd":7.4,"median":8.3,"1sd":9.2,"2sd":10.3,"3sd":11.4},{"umur":8,"min3sd":6.2,"min2sd":6.9,"min1sd":7.7,"median":8.6,"1sd":9.6,"2sd":10.7,"3sd":11.9},{"umur":9,"min3sd":6.4,"min2sd":7.1,"min1sd":8,"median":8.9,"1sd":9.9,"2sd":11,"3sd":12.3},{"umur":10,"min3sd":6.6,"min2sd":7.4,"min1sd":8.2,"median":9.2,"1sd":10.2,"2sd":11.4,"3sd":12.7},{"umur":11,"min3sd":6.8,"min2sd":7.6,"min1sd":8.4,"median":9.4,"1sd":10.5,"2sd":11.7,"3sd":13},{"umur":12,"min3sd":6.9,"min2sd":7.7,"min1sd":8.6,"median":9.6,"1sd":10.8,"2sd":12,"3sd":13.3},{"umur":13,"min3sd":7.1,"min2sd":7.9,"min1sd":8.8,"median":9.9,"1sd":11,"2sd":12.3,"3sd":13.7},{"umur":14,"min3sd":7.2,"min2sd":8.1,"min1sd":9,"median":10.1,"1sd":11.3,"2sd":12.6,"3sd":14},{"umur":15,"min3sd":7.4,"min2sd":8.3,"min1sd":9.2,"median":10.3,"1sd":11.5,"2sd":12.8,"3sd":14.3},{"umur":16,"min3sd":7.5,"min2sd":8.4,"min1sd":9.4,"median":10.5,"1sd":11.7,"2sd":13.1,"3sd":14.6},{"umur":17,"min3sd":7.7,"min2sd":8.6,"min1sd":9.6,"median":10.7,"1sd":12,"2sd":13.4,"3sd":14.9},{"umur":18,"min3sd":7.8,"min2sd":8.8,"min1sd":9.8,"median":10.9,"1sd":12.2,"2sd":13.7,"3sd":15.3},{"umur":19,"min3sd":8,"min2sd":8.9,"min1sd":10,"median":11.1,"1sd":12.5,"2sd":13.9,"3sd":15.6},{"umur":20,"min3sd":8.1,"min2sd":9.1,"min1sd":10.1,"median":11.3,"1sd":12.7,"2sd":14.2,"3sd":15.9},{"umur":21,"min3sd":8.2,"min2sd":9.2,"min1sd":10.3,"median":11.5,"1sd":12.9,"2sd":14.5,"3sd":16.2},{"umur":22,"min3sd":8.4,"min2sd":9.4,"min1sd":10.5,"median":11.8,"1sd":13.2,"2sd":14.7,"3sd":16.5},{"umur":23,"min3sd":8.5,"min2sd":9.5,"min1sd":10.7,"median":12,"1sd":13.4,"2sd":15,"3sd":16.8},{"umur":24,"min3sd":8.6,"min2sd":9.7,"min1sd":10.8,"median":12.2,"1sd":13.6,"2sd":15.3,"3sd":17.1},{"umur":25,"min3sd":8.8,"min2sd":9.8,"min1sd":11,"median":12.4,"1sd":13.9,"2sd":15.5,"3sd":17.5},{"umur":26,"min3sd":8.9,"min2sd":10,"min1sd":11.2,"median":12.5,"1sd":14.1,"2sd":15.8,"3sd":17.8},{"umur":27,"min3sd":9,"min2sd":10.1,"min1sd":11.3,"median":12.7,"1sd":14.3,"2sd":16.1,"3sd":18.1},{"umur":28,"min3sd":9.1,"min2sd":10.2,"min1sd":11.5,"median":12.9,"1sd":14.5,"2sd":16.3,"3sd":18.4},{"umur":29,"min3sd":9.2,"min2sd":10.4,"min1sd":11.7,"median":13.1,"1sd":14.8,"2sd":16.6,"3sd":18.7},{"umur":30,"min3sd":9.4,"min2sd":10.5,"min1sd":11.8,"median":13.3,"1sd":15,"2sd":16.9,"3sd":19},{"umur":31,"min3sd":9.5,"min2sd":10.7,"min1sd":12,"median":13.5,"1sd":15.2,"2sd":17.1,"3sd":19.3},{"umur":32,"min3sd":9.6,"min2sd":10.8,"min1sd":12.1,"median":13.7,"1sd":15.4,"2sd":17.4,"3sd":19.6},{"umur":33,"min3sd":9.7,"min2sd":10.9,"min1sd":12.3,"median":13.8,"1sd":15.6,"2sd":17.6,"3sd":19.9},{"umur":34,"min3sd":9.8,"min2sd":11,"min1sd":12.4,"median":14,"1sd":15.8,"2sd":17.8,"3sd":20.2},{"umur":35,"min3sd":9.9,"min2sd":11.2,"min1sd":12.6,"median":14.2,"1sd":16,"2sd":18.1,"3sd":20.4},{"umur":36,"min3sd":10,"min2sd":11.3,"min1sd":12.7,"median":14.3,"1sd":16.2,"2sd":18.3,"3sd":20.7},{"umur":37,"min3sd":10.1,"min2sd":11.4,"min1sd":12.9,"median":14.5,"1sd":16.4,"2sd":18.6,"3sd":21},{"umur":38,"min3sd":10.2,"min2sd":11.5,"min1sd":13,"median":14.7,"1sd":16.6,"2sd":18.8,"3sd":21.3},{"umur":39,"min3sd":10.3,"min2sd":11.6,"min1sd":13.1,"median":14.8,"1sd":16.8,"2sd":19,"3sd":21.6},{"umur":40,"min3sd":10.4,"min2sd":11.8,"min1sd":13.3,"median":15,"1sd":17,"2sd":19.3,"3sd":21.9},{"umur":41,"min3sd":10.5,"min2sd":11.9,"min1sd":13.4,"median":15.2,"1sd":17.2,"2sd":19.5,"3sd":22.1},{"umur":42,"min3sd":10.6,"min2sd":12,"min1sd":13.6,"median":15.3,"1sd":17.4,"2sd":19.7,"3sd":22.4},{"umur":43,"min3sd":10.7,"min2sd":12.1,"min1sd":13.7,"median":15.5,"1sd":17.6,"2sd":20,"3sd":22.7},{"umur":44,"min3sd":10.8,"min2sd":12.2,"min1sd":13.8,"median":15.7,"1sd":17.8,"2sd":20.2,"3sd":23},{"umur":45,"min3sd":10.9,"min2sd":12.4,"min1sd":14,"median":15.8,"1sd":18,"2sd":20.5,"3sd":23.3},{"umur":46,"min3sd":11,"min2sd":12.5,"min1sd":14.1,"median":16,"1sd":18.2,"2sd":20.7,"3sd":23.6},{"umur":47,"min3sd":11.1,"min2sd":12.6,"min1sd":14.3,"median":16.2,"1sd":18.4,"2sd":20.9,"3sd":23.9},{"umur":48,"min3sd":11.2,"min2sd":12.7,"min1sd":14.4,"median":16.3,"1sd":18.6,"2sd":21.2,"3sd":24.2},{"umur":49,"min3sd":11.3,"min2sd":12.8,"min1sd":14.5,"median":16.5,"1sd":18.8,"2sd":21.4,"3sd":24.5},{"umur":50,"min3sd":11.4,"min2sd":12.9,"min1sd":14.7,"median":16.7,"1sd":19,"2sd":21.7,"3sd":24.8},{"umur":51,"min3sd":11.5,"min2sd":13.1,"min1sd":14.8,"median":16.8,"1sd":19.2,"2sd":21.9,"3sd":25.1},{"umur":52,"min3sd":11.6,"min2sd":13.2,"min1sd":15,"median":17,"1sd":19.4,"2sd":22.2,"3sd":25.4},{"umur":53,"min3sd":11.7,"min2sd":13.3,"min1sd":15.1,"median":17.2,"1sd":19.6,"2sd":22.4,"3sd":25.7},{"umur":54,"min3sd":11.8,"min2sd":13.4,"min1sd":15.2,"median":17.3,"1sd":19.8,"2sd":22.7,"3sd":26},{"umur":55,"min3sd":11.9,"min2sd":13.5,"min1sd":15.4,"median":17.5,"1sd":20,"2sd":22.9,"3sd":26.3},{"umur":56,"min3sd":12,"min2sd":13.6,"min1sd":15.5,"median":17.7,"1sd":20.2,"2sd":23.2,"3sd":26.6},{"umur":57,"min3sd":12.1,"min2sd":13.7,"min1sd":15.6,"median":17.8,"1sd":20.4,"2sd":23.4,"3sd":26.9},{"umur":58,"min3sd":12.2,"min2sd":13.8,"min1sd":15.8,"median":18,"1sd":20.6,"2sd":23.7,"3sd":27.2},{"umur":59,"min3sd":12.3,"min2sd":14,"min1sd":15.9,"median":18.2,"1sd":20.8,"2sd":23.9,"3sd":27.6},{"umur":60,"min3sd":12.4,"min2sd":14.1,"min1sd":16,"median":18.3,"1sd":21,"2sd":24.2,"3sd":27.9}]', true);
    
        return $data;
    }
    
    public static function table_bb_u_perempuan()
    {
        $data=json_decode('[{"umur":0,"min3sd":2,"min2sd":2.4,"min1sd":2.8,"median":3.2,"1sd":3.7,"2sd":4.2,"3sd":4.8},{"umur":1,"min3sd":2.7,"min2sd":3.2,"min1sd":3.6,"median":4.2,"1sd":4.8,"2sd":5.5,"3sd":6.2},{"umur":2,"min3sd":3.4,"min2sd":3.9,"min1sd":4.5,"median":5.1,"1sd":5.8,"2sd":6.6,"3sd":7.5},{"umur":3,"min3sd":4,"min2sd":4.5,"min1sd":5.2,"median":5.8,"1sd":6.6,"2sd":7.5,"3sd":8.5},{"umur":4,"min3sd":4.4,"min2sd":5,"min1sd":5.7,"median":6.4,"1sd":7.3,"2sd":8.2,"3sd":9.3},{"umur":5,"min3sd":4.8,"min2sd":5.4,"min1sd":6.1,"median":6.9,"1sd":7.8,"2sd":8.8,"3sd":10},{"umur":6,"min3sd":5.1,"min2sd":5.7,"min1sd":6.5,"median":7.3,"1sd":8.2,"2sd":9.3,"3sd":10.6},{"umur":7,"min3sd":5.3,"min2sd":6,"min1sd":6.8,"median":7.6,"1sd":8.6,"2sd":9.8,"3sd":11.1},{"umur":8,"min3sd":5.6,"min2sd":6.3,"min1sd":7,"median":7.9,"1sd":9,"2sd":10.2,"3sd":11.6},{"umur":9,"min3sd":5.8,"min2sd":6.5,"min1sd":7.3,"median":8.2,"1sd":9.3,"2sd":10.5,"3sd":12},{"umur":10,"min3sd":5.9,"min2sd":6.7,"min1sd":7.5,"median":8.5,"1sd":9.6,"2sd":10.9,"3sd":12.4},{"umur":11,"min3sd":6.1,"min2sd":6.9,"min1sd":7.7,"median":8.7,"1sd":9.9,"2sd":11.2,"3sd":12.8},{"umur":12,"min3sd":6.3,"min2sd":7,"min1sd":7.9,"median":8.9,"1sd":10.1,"2sd":11.5,"3sd":13.1},{"umur":13,"min3sd":6.4,"min2sd":7.2,"min1sd":8.1,"median":9.2,"1sd":10.4,"2sd":11.8,"3sd":13.5},{"umur":14,"min3sd":6.6,"min2sd":7.4,"min1sd":8.3,"median":9.4,"1sd":10.6,"2sd":12.1,"3sd":13.8},{"umur":15,"min3sd":6.7,"min2sd":7.6,"min1sd":8.5,"median":9.6,"1sd":10.9,"2sd":12.4,"3sd":14.1},{"umur":16,"min3sd":6.9,"min2sd":7.7,"min1sd":8.7,"median":9.8,"1sd":11.1,"2sd":12.6,"3sd":14.5},{"umur":17,"min3sd":7,"min2sd":7.9,"min1sd":8.9,"median":10,"1sd":11.4,"2sd":12.9,"3sd":14.8},{"umur":18,"min3sd":7.2,"min2sd":8.1,"min1sd":9.1,"median":10.2,"1sd":11.6,"2sd":13.2,"3sd":15.1},{"umur":19,"min3sd":7.3,"min2sd":8.2,"min1sd":9.2,"median":10.4,"1sd":11.8,"2sd":13.5,"3sd":15.4},{"umur":20,"min3sd":7.5,"min2sd":8.4,"min1sd":9.4,"median":10.6,"1sd":12.1,"2sd":13.7,"3sd":15.7},{"umur":21,"min3sd":7.6,"min2sd":8.6,"min1sd":9.6,"median":10.9,"1sd":12.3,"2sd":14,"3sd":16},{"umur":22,"min3sd":7.8,"min2sd":8.7,"min1sd":9.8,"median":11.1,"1sd":12.5,"2sd":14.3,"3sd":16.4},{"umur":23,"min3sd":7.9,"min2sd":8.9,"min1sd":10,"median":11.3,"1sd":12.8,"2sd":14.6,"3sd":16.7},{"umur":24,"min3sd":8.1,"min2sd":9,"min1sd":10.2,"median":11.5,"1sd":13,"2sd":14.8,"3sd":17},{"umur":25,"min3sd":8.2,"min2sd":9.2,"min1sd":10.3,"median":11.7,"1sd":13.3,"2sd":15.1,"3sd":17.3},{"umur":26,"min3sd":8.4,"min2sd":9.4,"min1sd":10.5,"median":11.9,"1sd":13.5,"2sd":15.4,"3sd":17.7},{"umur":27,"min3sd":8.5,"min2sd":9.5,"min1sd":10.7,"median":12.1,"1sd":13.7,"2sd":15.7,"3sd":18},{"umur":28,"min3sd":8.6,"min2sd":9.7,"min1sd":10.9,"median":12.3,"1sd":14,"2sd":16,"3sd":18.3},{"umur":29,"min3sd":8.8,"min2sd":9.8,"min1sd":11.1,"median":12.5,"1sd":14.2,"2sd":16.2,"3sd":18.7},{"umur":30,"min3sd":8.9,"min2sd":10,"min1sd":11.2,"median":12.7,"1sd":14.4,"2sd":16.5,"3sd":19},{"umur":31,"min3sd":9,"min2sd":10.1,"min1sd":11.4,"median":12.9,"1sd":14.7,"2sd":16.8,"3sd":19.3},{"umur":32,"min3sd":9.1,"min2sd":10.3,"min1sd":11.6,"median":13.1,"1sd":14.9,"2sd":17.1,"3sd":19.6},{"umur":33,"min3sd":9.3,"min2sd":10.4,"min1sd":11.7,"median":13.3,"1sd":15.1,"2sd":17.3,"3sd":20},{"umur":34,"min3sd":9.4,"min2sd":10.5,"min1sd":11.9,"median":13.5,"1sd":15.4,"2sd":17.6,"3sd":20.3},{"umur":35,"min3sd":9.5,"min2sd":10.7,"min1sd":12,"median":13.7,"1sd":15.6,"2sd":17.9,"3sd":20.6},{"umur":36,"min3sd":9.6,"min2sd":10.8,"min1sd":12.2,"median":13.9,"1sd":15.8,"2sd":18.1,"3sd":20.9},{"umur":37,"min3sd":9.7,"min2sd":10.9,"min1sd":12.4,"median":14,"1sd":16,"2sd":18.4,"3sd":21.3},{"umur":38,"min3sd":9.8,"min2sd":11.1,"min1sd":12.5,"median":14.2,"1sd":16.3,"2sd":18.7,"3sd":21.6},{"umur":39,"min3sd":9.9,"min2sd":11.2,"min1sd":12.7,"median":14.4,"1sd":16.5,"2sd":19,"3sd":22},{"umur":40,"min3sd":10.1,"min2sd":11.3,"min1sd":12.8,"median":14.6,"1sd":16.7,"2sd":19.2,"3sd":22.3},{"umur":41,"min3sd":10.2,"min2sd":11.5,"min1sd":13,"median":14.8,"1sd":16.9,"2sd":19.5,"3sd":22.7},{"umur":42,"min3sd":10.3,"min2sd":11.6,"min1sd":13.1,"median":15,"1sd":17.2,"2sd":19.8,"3sd":23},{"umur":43,"min3sd":10.4,"min2sd":11.7,"min1sd":13.3,"median":15.2,"1sd":17.4,"2sd":20.1,"3sd":23.4},{"umur":44,"min3sd":10.5,"min2sd":11.8,"min1sd":13.4,"median":15.3,"1sd":17.6,"2sd":20.4,"3sd":23.7},{"umur":45,"min3sd":10.6,"min2sd":12,"min1sd":13.6,"median":15.5,"1sd":17.8,"2sd":20.7,"3sd":24.1},{"umur":46,"min3sd":10.7,"min2sd":12.1,"min1sd":13.7,"median":15.7,"1sd":18.1,"2sd":20.9,"3sd":24.5},{"umur":47,"min3sd":10.8,"min2sd":12.2,"min1sd":13.9,"median":15.9,"1sd":18.3,"2sd":21.2,"3sd":24.8},{"umur":48,"min3sd":10.9,"min2sd":12.3,"min1sd":14,"median":16.1,"1sd":18.5,"2sd":21.5,"3sd":25.2},{"umur":49,"min3sd":11,"min2sd":12.4,"min1sd":14.2,"median":16.3,"1sd":18.8,"2sd":21.8,"3sd":25.5},{"umur":50,"min3sd":11.1,"min2sd":12.6,"min1sd":14.3,"median":16.4,"1sd":19,"2sd":22.1,"3sd":25.9},{"umur":51,"min3sd":11.2,"min2sd":12.7,"min1sd":14.5,"median":16.6,"1sd":19.2,"2sd":22.4,"3sd":26.3},{"umur":52,"min3sd":11.3,"min2sd":12.8,"min1sd":14.6,"median":16.8,"1sd":19.4,"2sd":22.6,"3sd":26.6},{"umur":53,"min3sd":11.4,"min2sd":12.9,"min1sd":14.8,"median":17,"1sd":19.7,"2sd":22.9,"3sd":27},{"umur":54,"min3sd":11.5,"min2sd":13,"min1sd":14.9,"median":17.2,"1sd":19.9,"2sd":23.2,"3sd":27.4},{"umur":55,"min3sd":11.6,"min2sd":13.2,"min1sd":15.1,"median":17.3,"1sd":20.1,"2sd":23.5,"3sd":27.7},{"umur":56,"min3sd":11.7,"min2sd":13.3,"min1sd":15.2,"median":17.5,"1sd":20.3,"2sd":23.8,"3sd":28.1},{"umur":57,"min3sd":11.8,"min2sd":13.4,"min1sd":15.3,"median":17.7,"1sd":20.6,"2sd":24.1,"3sd":28.5},{"umur":58,"min3sd":11.9,"min2sd":13.5,"min1sd":15.5,"median":17.9,"1sd":20.8,"2sd":24.4,"3sd":28.8},{"umur":59,"min3sd":12,"min2sd":13.6,"min1sd":15.6,"median":18,"1sd":21,"2sd":24.6,"3sd":29.2},{"umur":60,"min3sd":12.1,"min2sd":13.7,"min1sd":15.8,"median":18.2,"1sd":21.2,"2sd":24.9,"3sd":29.5}]', true);
    
        return $data;
    }
    
    public static function table_pb_u_laki_laki()
    {
        $data=json_decode('[{"umur":0,"min3sd":44.2,"min2sd":46.1,"min1sd":48,"median":49.9,"1sd":51.8,"2sd":53.7,"3sd":55.6},{"umur":1,"min3sd":48.9,"min2sd":50.8,"min1sd":52.8,"median":54.7,"1sd":56.7,"2sd":58.6,"3sd":60.6},{"umur":2,"min3sd":52.4,"min2sd":54.4,"min1sd":56.4,"median":58.4,"1sd":60.4,"2sd":62.4,"3sd":64.4},{"umur":3,"min3sd":55.3,"min2sd":57.3,"min1sd":59.4,"median":61.4,"1sd":63.5,"2sd":65.5,"3sd":67.6},{"umur":4,"min3sd":57.6,"min2sd":59.7,"min1sd":61.8,"median":63.9,"1sd":66,"2sd":68,"3sd":70.1},{"umur":5,"min3sd":59.6,"min2sd":61.7,"min1sd":63.8,"median":65.9,"1sd":68,"2sd":70.1,"3sd":72.2},{"umur":6,"min3sd":61.2,"min2sd":63.3,"min1sd":65.5,"median":67.6,"1sd":69.8,"2sd":71.9,"3sd":74},{"umur":7,"min3sd":62.7,"min2sd":64.8,"min1sd":67,"median":69.2,"1sd":71.3,"2sd":73.5,"3sd":75.7},{"umur":8,"min3sd":64,"min2sd":66.2,"min1sd":68.4,"median":70.6,"1sd":72.8,"2sd":75,"3sd":77.2},{"umur":9,"min3sd":65.2,"min2sd":67.5,"min1sd":69.7,"median":72,"1sd":74.2,"2sd":76.5,"3sd":78.7},{"umur":10,"min3sd":66.4,"min2sd":68.7,"min1sd":71,"median":73.3,"1sd":75.6,"2sd":77.9,"3sd":80.1},{"umur":11,"min3sd":67.6,"min2sd":69.9,"min1sd":72.2,"median":74.5,"1sd":76.9,"2sd":79.2,"3sd":81.5},{"umur":12,"min3sd":68.6,"min2sd":71,"min1sd":73.4,"median":75.7,"1sd":78.1,"2sd":80.5,"3sd":82.9},{"umur":13,"min3sd":69.6,"min2sd":72.1,"min1sd":74.5,"median":76.9,"1sd":79.3,"2sd":81.8,"3sd":84.2},{"umur":14,"min3sd":70.6,"min2sd":73.1,"min1sd":75.6,"median":78,"1sd":80.5,"2sd":83,"3sd":85.5},{"umur":15,"min3sd":71.6,"min2sd":74.1,"min1sd":76.6,"median":79.1,"1sd":81.7,"2sd":84.2,"3sd":86.7},{"umur":16,"min3sd":72.5,"min2sd":75,"min1sd":77.6,"median":80.2,"1sd":82.8,"2sd":85.4,"3sd":88},{"umur":17,"min3sd":73.3,"min2sd":76,"min1sd":78.6,"median":81.2,"1sd":83.9,"2sd":86.5,"3sd":89.2},{"umur":18,"min3sd":74.2,"min2sd":76.9,"min1sd":79.6,"median":82.3,"1sd":85,"2sd":87.7,"3sd":90.4},{"umur":19,"min3sd":75,"min2sd":77.7,"min1sd":80.5,"median":83.2,"1sd":86,"2sd":88.8,"3sd":91.5},{"umur":20,"min3sd":75.8,"min2sd":78.6,"min1sd":81.4,"median":84.2,"1sd":87,"2sd":89.8,"3sd":92.6},{"umur":21,"min3sd":76.5,"min2sd":79.4,"min1sd":82.3,"median":85.1,"1sd":88,"2sd":90.9,"3sd":93.8},{"umur":22,"min3sd":77.2,"min2sd":80.2,"min1sd":83.1,"median":86,"1sd":89,"2sd":91.9,"3sd":94.9},{"umur":23,"min3sd":78,"min2sd":81,"min1sd":83.9,"median":86.9,"1sd":89.9,"2sd":92.9,"3sd":95.9},{"umur":24,"metode":"telentang","min3sd":78.7,"min2sd":81.7,"min1sd":84.8,"median":87.8,"1sd":90.9,"2sd":93.9,"3sd":97},{"umur":24,"metode":"berdiri","min3sd":78,"min2sd":81,"min1sd":84.1,"median":87.1,"1sd":90.2,"2sd":93.2,"3sd":96.3},{"umur":25,"min3sd":78.6,"min2sd":81.7,"min1sd":84.9,"median":88,"1sd":91.1,"2sd":94.2,"3sd":97.3},{"umur":26,"min3sd":79.3,"min2sd":82.5,"min1sd":85.6,"median":88.8,"1sd":92,"2sd":95.2,"3sd":98.3},{"umur":27,"min3sd":79.9,"min2sd":83.1,"min1sd":86.4,"median":89.6,"1sd":92.9,"2sd":96.1,"3sd":99.3},{"umur":28,"min3sd":80.5,"min2sd":83.8,"min1sd":87.1,"median":90.4,"1sd":93.7,"2sd":97,"3sd":100.3},{"umur":29,"min3sd":81.1,"min2sd":84.5,"min1sd":87.8,"median":91.2,"1sd":94.5,"2sd":97.9,"3sd":101.2},{"umur":30,"min3sd":81.7,"min2sd":85.1,"min1sd":88.5,"median":91.9,"1sd":95.3,"2sd":98.7,"3sd":102.1},{"umur":31,"min3sd":82.3,"min2sd":85.7,"min1sd":89.2,"median":92.7,"1sd":96.1,"2sd":99.6,"3sd":103},{"umur":32,"min3sd":82.8,"min2sd":86.4,"min1sd":89.9,"median":93.4,"1sd":96.9,"2sd":100.4,"3sd":103.9},{"umur":33,"min3sd":83.4,"min2sd":86.9,"min1sd":90.5,"median":94.1,"1sd":97.6,"2sd":101.2,"3sd":104.8},{"umur":34,"min3sd":83.9,"min2sd":87.5,"min1sd":91.1,"median":94.8,"1sd":98.4,"2sd":102,"3sd":105.6},{"umur":35,"min3sd":84.4,"min2sd":88.1,"min1sd":91.8,"median":95.4,"1sd":99.1,"2sd":102.7,"3sd":106.4},{"umur":36,"min3sd":85,"min2sd":88.7,"min1sd":92.4,"median":96.1,"1sd":99.8,"2sd":103.5,"3sd":107.2},{"umur":37,"min3sd":85.5,"min2sd":89.2,"min1sd":93,"median":96.7,"1sd":100.5,"2sd":104.2,"3sd":108},{"umur":38,"min3sd":86,"min2sd":89.8,"min1sd":93.6,"median":97.4,"1sd":101.2,"2sd":105,"3sd":108.8},{"umur":39,"min3sd":86.5,"min2sd":90.3,"min1sd":94.2,"median":98,"1sd":101.8,"2sd":105.7,"3sd":109.5},{"umur":40,"min3sd":87,"min2sd":90.9,"min1sd":94.7,"median":98.6,"1sd":102.5,"2sd":106.4,"3sd":110.3},{"umur":41,"min3sd":87.5,"min2sd":91.4,"min1sd":95.3,"median":99.2,"1sd":103.2,"2sd":107.1,"3sd":111},{"umur":42,"min3sd":88,"min2sd":91.9,"min1sd":95.9,"median":99.9,"1sd":103.8,"2sd":107.8,"3sd":111.7},{"umur":43,"min3sd":88.4,"min2sd":92.4,"min1sd":96.4,"median":100.4,"1sd":104.5,"2sd":108.5,"3sd":112.5},{"umur":44,"min3sd":88.9,"min2sd":93,"min1sd":97,"median":101,"1sd":105.1,"2sd":109.1,"3sd":113.2},{"umur":45,"min3sd":89.4,"min2sd":93.5,"min1sd":97.5,"median":101.6,"1sd":105.7,"2sd":109.8,"3sd":113.9},{"umur":46,"min3sd":89.8,"min2sd":94,"min1sd":98.1,"median":102.2,"1sd":106.3,"2sd":110.4,"3sd":114.6},{"umur":47,"min3sd":90.3,"min2sd":94.4,"min1sd":98.6,"median":102.8,"1sd":106.9,"2sd":111.1,"3sd":115.2},{"umur":48,"min3sd":90.7,"min2sd":94.9,"min1sd":99.1,"median":103.3,"1sd":107.5,"2sd":111.7,"3sd":115.9},{"umur":49,"min3sd":91.2,"min2sd":95.4,"min1sd":99.7,"median":103.9,"1sd":108.1,"2sd":112.4,"3sd":116.6},{"umur":50,"min3sd":91.6,"min2sd":95.9,"min1sd":100.2,"median":104.4,"1sd":108.7,"2sd":113,"3sd":117.3},{"umur":51,"min3sd":92.1,"min2sd":96.4,"min1sd":100.7,"median":105,"1sd":109.3,"2sd":113.6,"3sd":117.9},{"umur":52,"min3sd":92.5,"min2sd":96.9,"min1sd":101.2,"median":105.6,"1sd":109.9,"2sd":114.2,"3sd":118.6},{"umur":53,"min3sd":93,"min2sd":97.4,"min1sd":101.7,"median":106.1,"1sd":110.5,"2sd":114.9,"3sd":119.2},{"umur":54,"min3sd":93.4,"min2sd":97.8,"min1sd":102.3,"median":106.7,"1sd":111.1,"2sd":115.5,"3sd":119.9},{"umur":55,"min3sd":93.9,"min2sd":98.3,"min1sd":102.8,"median":107.2,"1sd":111.7,"2sd":116.1,"3sd":120.6},{"umur":56,"min3sd":94.3,"min2sd":98.8,"min1sd":103.3,"median":107.8,"1sd":112.3,"2sd":116.7,"3sd":121.2},{"umur":57,"min3sd":94.7,"min2sd":99.3,"min1sd":103.8,"median":108.3,"1sd":112.8,"2sd":117.4,"3sd":121.9},{"umur":58,"min3sd":95.2,"min2sd":99.7,"min1sd":104.3,"median":108.9,"1sd":113.4,"2sd":118,"3sd":122.6},{"umur":59,"min3sd":95.6,"min2sd":100.2,"min1sd":104.8,"median":109.4,"1sd":114,"2sd":118.6,"3sd":123.2},{"umur":60,"min3sd":96.1,"min2sd":100.7,"min1sd":105.3,"median":110,"1sd":114.6,"2sd":119.2,"3sd":123.9}]', true);
    
        return $data;
    }
    
    public static function table_pb_u_perempuan()
    {
        $data=json_decode('[{"umur":0,"min3sd":43.6,"min2sd":45.4,"min1sd":47.3,"median":49.1,"1sd":51,"2sd":52.9,"3sd":54.7},{"umur":1,"min3sd":47.8,"min2sd":49.8,"min1sd":51.7,"median":53.7,"1sd":55.6,"2sd":57.6,"3sd":59.5},{"umur":2,"min3sd":51,"min2sd":53,"min1sd":55,"median":57.1,"1sd":59.1,"2sd":61.1,"3sd":63.2},{"umur":3,"min3sd":53.5,"min2sd":55.6,"min1sd":57.7,"median":59.8,"1sd":61.9,"2sd":64,"3sd":66.1},{"umur":4,"min3sd":55.6,"min2sd":57.8,"min1sd":59.9,"median":62.1,"1sd":64.3,"2sd":66.4,"3sd":68.6},{"umur":5,"min3sd":57.4,"min2sd":59.6,"min1sd":61.8,"median":64,"1sd":66.2,"2sd":68.5,"3sd":70.7},{"umur":6,"min3sd":58.9,"min2sd":61.2,"min1sd":63.5,"median":65.7,"1sd":68,"2sd":70.3,"3sd":72.5},{"umur":7,"min3sd":60.3,"min2sd":62.7,"min1sd":65,"median":67.3,"1sd":69.6,"2sd":71.9,"3sd":74.2},{"umur":8,"min3sd":61.7,"min2sd":64,"min1sd":66.4,"median":68.7,"1sd":71.1,"2sd":73.5,"3sd":75.8},{"umur":9,"min3sd":62.9,"min2sd":65.3,"min1sd":67.7,"median":70.1,"1sd":72.6,"2sd":75,"3sd":77.4},{"umur":10,"min3sd":64.1,"min2sd":66.5,"min1sd":69,"median":71.5,"1sd":73.9,"2sd":76.4,"3sd":78.9},{"umur":11,"min3sd":65.2,"min2sd":67.7,"min1sd":70.3,"median":72.8,"1sd":75.3,"2sd":77.8,"3sd":80.3},{"umur":12,"min3sd":66.3,"min2sd":68.9,"min1sd":71.4,"median":74,"1sd":76.6,"2sd":79.2,"3sd":81.7},{"umur":13,"min3sd":67.3,"min2sd":70,"min1sd":72.6,"median":75.2,"1sd":77.8,"2sd":80.5,"3sd":83.1},{"umur":14,"min3sd":68.3,"min2sd":71,"min1sd":73.7,"median":76.4,"1sd":79.1,"2sd":81.7,"3sd":84.4},{"umur":15,"min3sd":69.3,"min2sd":72,"min1sd":74.8,"median":77.5,"1sd":80.2,"2sd":83,"3sd":85.7},{"umur":16,"min3sd":70.2,"min2sd":73,"min1sd":75.8,"median":78.6,"1sd":81.4,"2sd":84.2,"3sd":87},{"umur":17,"min3sd":71.1,"min2sd":74,"min1sd":76.8,"median":79.7,"1sd":82.5,"2sd":85.4,"3sd":88.2},{"umur":18,"min3sd":72,"min2sd":74.9,"min1sd":77.8,"median":80.7,"1sd":83.6,"2sd":86.5,"3sd":89.4},{"umur":19,"min3sd":72.8,"min2sd":75.8,"min1sd":78.8,"median":81.7,"1sd":84.7,"2sd":87.6,"3sd":90.6},{"umur":20,"min3sd":73.7,"min2sd":76.7,"min1sd":79.7,"median":82.7,"1sd":85.7,"2sd":88.7,"3sd":91.7},{"umur":21,"min3sd":74.5,"min2sd":77.5,"min1sd":80.6,"median":83.7,"1sd":86.7,"2sd":89.8,"3sd":92.9},{"umur":22,"min3sd":75.2,"min2sd":78.4,"min1sd":81.5,"median":84.6,"1sd":87.7,"2sd":90.8,"3sd":94},{"umur":23,"min3sd":76,"min2sd":79.2,"min1sd":82.3,"median":85.5,"1sd":88.7,"2sd":91.9,"3sd":95},{"umur":24,"metode":"telentang","min3sd":76.7,"min2sd":80,"min1sd":83.2,"median":86.4,"1sd":89.6,"2sd":92.9,"3sd":96.1},{"umur":24,"metode":"berdiri","min3sd":76,"min2sd":79.3,"min1sd":82.5,"median":85.7,"1sd":88.9,"2sd":92.2,"3sd":95.4},{"umur":25,"min3sd":76.8,"min2sd":80,"min1sd":83.3,"median":86.6,"1sd":89.9,"2sd":93.1,"3sd":96.4},{"umur":26,"min3sd":77.5,"min2sd":80.8,"min1sd":84.1,"median":87.4,"1sd":90.8,"2sd":94.1,"3sd":97.4},{"umur":27,"min3sd":78.1,"min2sd":81.5,"min1sd":84.9,"median":88.3,"1sd":91.7,"2sd":95,"3sd":98.4},{"umur":28,"min3sd":78.8,"min2sd":82.2,"min1sd":85.7,"median":89.1,"1sd":92.5,"2sd":96,"3sd":99.4},{"umur":29,"min3sd":79.5,"min2sd":82.9,"min1sd":86.4,"median":89.9,"1sd":93.4,"2sd":96.9,"3sd":100.3},{"umur":30,"min3sd":80.1,"min2sd":83.6,"min1sd":87.1,"median":90.7,"1sd":94.2,"2sd":97.7,"3sd":101.3},{"umur":31,"min3sd":80.7,"min2sd":84.3,"min1sd":87.9,"median":91.4,"1sd":95,"2sd":98.6,"3sd":102.2},{"umur":32,"min3sd":81.3,"min2sd":84.9,"min1sd":88.6,"median":92.2,"1sd":95.8,"2sd":99.4,"3sd":103.1},{"umur":33,"min3sd":81.9,"min2sd":85.6,"min1sd":89.3,"median":92.9,"1sd":96.6,"2sd":100.3,"3sd":103.9},{"umur":34,"min3sd":82.5,"min2sd":86.2,"min1sd":89.9,"median":93.6,"1sd":97.4,"2sd":101.1,"3sd":104.8},{"umur":35,"min3sd":83.1,"min2sd":86.8,"min1sd":90.6,"median":94.4,"1sd":98.1,"2sd":101.9,"3sd":105.6},{"umur":36,"min3sd":83.6,"min2sd":87.4,"min1sd":91.2,"median":95.1,"1sd":98.9,"2sd":102.7,"3sd":106.5},{"umur":37,"min3sd":84.2,"min2sd":88,"min1sd":91.9,"median":95.7,"1sd":99.6,"2sd":103.4,"3sd":107.3},{"umur":38,"min3sd":84.7,"min2sd":88.6,"min1sd":92.5,"median":96.4,"1sd":100.3,"2sd":104.2,"3sd":108.1},{"umur":39,"min3sd":85.3,"min2sd":89.2,"min1sd":93.1,"median":97.1,"1sd":101,"2sd":105,"3sd":108.9},{"umur":40,"min3sd":85.8,"min2sd":89.8,"min1sd":93.8,"median":97.7,"1sd":101.7,"2sd":105.7,"3sd":109.7},{"umur":41,"min3sd":86.3,"min2sd":90.4,"min1sd":94.4,"median":98.4,"1sd":102.4,"2sd":106.4,"3sd":110.5},{"umur":42,"min3sd":86.8,"min2sd":90.9,"min1sd":95,"median":99,"1sd":103.1,"2sd":107.2,"3sd":111.2},{"umur":43,"min3sd":87.4,"min2sd":91.5,"min1sd":95.6,"median":99.7,"1sd":103.8,"2sd":107.9,"3sd":112},{"umur":44,"min3sd":87.9,"min2sd":92,"min1sd":96.2,"median":100.3,"1sd":104.5,"2sd":108.6,"3sd":112.7},{"umur":45,"min3sd":88.4,"min2sd":92.5,"min1sd":96.7,"median":100.9,"1sd":105.1,"2sd":109.3,"3sd":113.5},{"umur":46,"min3sd":88.9,"min2sd":93.1,"min1sd":97.3,"median":101.5,"1sd":105.8,"2sd":110,"3sd":114.2},{"umur":47,"min3sd":89.3,"min2sd":93.6,"min1sd":97.9,"median":102.1,"1sd":106.4,"2sd":110.7,"3sd":114.9},{"umur":48,"min3sd":89.8,"min2sd":94.1,"min1sd":98.4,"median":102.7,"1sd":107,"2sd":111.3,"3sd":115.7},{"umur":49,"min3sd":90.3,"min2sd":94.6,"min1sd":99,"median":103.3,"1sd":107.7,"2sd":112,"3sd":116.4},{"umur":50,"min3sd":90.7,"min2sd":95.1,"min1sd":99.5,"median":103.9,"1sd":108.3,"2sd":112.7,"3sd":117.1},{"umur":51,"min3sd":91.2,"min2sd":95.6,"min1sd":100.1,"median":104.5,"1sd":108.9,"2sd":113.3,"3sd":117.7},{"umur":52,"min3sd":91.7,"min2sd":96.1,"min1sd":100.6,"median":105,"1sd":109.5,"2sd":114,"3sd":118.4},{"umur":53,"min3sd":92.1,"min2sd":96.6,"min1sd":101.1,"median":105.6,"1sd":110.1,"2sd":114.6,"3sd":119.1},{"umur":54,"min3sd":92.6,"min2sd":97.1,"min1sd":101.6,"median":106.2,"1sd":110.7,"2sd":115.2,"3sd":119.8},{"umur":55,"min3sd":93,"min2sd":97.6,"min1sd":102.2,"median":106.7,"1sd":111.3,"2sd":115.9,"3sd":120.4},{"umur":56,"min3sd":93.4,"min2sd":98.1,"min1sd":102.7,"median":107.3,"1sd":111.9,"2sd":116.5,"3sd":121.1},{"umur":57,"min3sd":93.9,"min2sd":98.5,"min1sd":103.2,"median":107.8,"1sd":112.5,"2sd":117.1,"3sd":121.8},{"umur":58,"min3sd":94.3,"min2sd":99,"min1sd":103.7,"median":108.4,"1sd":113,"2sd":117.7,"3sd":122.4},{"umur":59,"min3sd":94.7,"min2sd":99.5,"min1sd":104.2,"median":108.9,"1sd":113.6,"2sd":118.3,"3sd":123.1},{"umur":60,"min3sd":95.2,"min2sd":99.9,"min1sd":104.7,"median":109.4,"1sd":114.2,"2sd":118.9,"3sd":123.7}]', true);
    
        return $data;
    }

    public static function table_bb_tb_024_laki_laki()
    {
        $data=json_decode('[{"tinggi":45,"min3sd":1.9,"min2sd":2,"min1sd":2.2,"median":2.4,"1sd":2.7,"2sd":3,"3sd":3.3},{"tinggi":45.5,"min3sd":1.9,"min2sd":2.1,"min1sd":2.3,"median":2.5,"1sd":2.8,"2sd":3.1,"3sd":3.4},{"tinggi":46,"min3sd":2,"min2sd":2.2,"min1sd":2.4,"median":2.6,"1sd":2.9,"2sd":3.1,"3sd":3.5},{"tinggi":46.5,"min3sd":2.1,"min2sd":2.3,"min1sd":2.5,"median":2.7,"1sd":3,"2sd":3.2,"3sd":3.6},{"tinggi":47,"min3sd":2.1,"min2sd":2.3,"min1sd":2.5,"median":2.8,"1sd":3,"2sd":3.3,"3sd":3.7},{"tinggi":47.5,"min3sd":2.2,"min2sd":2.4,"min1sd":2.6,"median":2.9,"1sd":3.1,"2sd":3.4,"3sd":3.8},{"tinggi":48,"min3sd":2.3,"min2sd":2.5,"min1sd":2.7,"median":2.9,"1sd":3.2,"2sd":3.6,"3sd":3.9},{"tinggi":48.5,"min3sd":2.3,"min2sd":2.6,"min1sd":2.8,"median":3,"1sd":3.3,"2sd":3.7,"3sd":4},{"tinggi":49,"min3sd":2.4,"min2sd":2.6,"min1sd":2.9,"median":3.1,"1sd":3.4,"2sd":3.8,"3sd":4.2},{"tinggi":49.5,"min3sd":2.5,"min2sd":2.7,"min1sd":3,"median":3.2,"1sd":3.5,"2sd":3.9,"3sd":4.3},{"tinggi":50,"min3sd":2.6,"min2sd":2.8,"min1sd":3,"median":3.3,"1sd":3.6,"2sd":4,"3sd":4.4},{"tinggi":50.5,"min3sd":2.7,"min2sd":2.9,"min1sd":3.1,"median":3.4,"1sd":3.8,"2sd":4.1,"3sd":4.5},{"tinggi":51,"min3sd":2.7,"min2sd":3,"min1sd":3.2,"median":3.5,"1sd":3.9,"2sd":4.2,"3sd":4.7},{"tinggi":51.5,"min3sd":2.8,"min2sd":3.1,"min1sd":3.3,"median":3.6,"1sd":4,"2sd":4.4,"3sd":4.8},{"tinggi":52,"min3sd":2.9,"min2sd":3.2,"min1sd":3.5,"median":3.8,"1sd":4.1,"2sd":4.5,"3sd":5},{"tinggi":52.5,"min3sd":3,"min2sd":3.3,"min1sd":3.6,"median":3.9,"1sd":4.2,"2sd":4.6,"3sd":5.1},{"tinggi":53,"min3sd":3.1,"min2sd":3.4,"min1sd":3.7,"median":4,"1sd":4.4,"2sd":4.8,"3sd":5.3},{"tinggi":53.5,"min3sd":3.2,"min2sd":3.5,"min1sd":3.8,"median":4.1,"1sd":4.5,"2sd":4.9,"3sd":5.4},{"tinggi":54,"min3sd":3.3,"min2sd":3.6,"min1sd":3.9,"median":4.3,"1sd":4.7,"2sd":5.1,"3sd":5.6},{"tinggi":54.5,"min3sd":3.4,"min2sd":3.7,"min1sd":4,"median":4.4,"1sd":4.8,"2sd":5.3,"3sd":5.8},{"tinggi":55,"min3sd":3.6,"min2sd":3.8,"min1sd":4.2,"median":4.5,"1sd":5,"2sd":5.4,"3sd":6},{"tinggi":55.5,"min3sd":3.7,"min2sd":4,"min1sd":4.3,"median":4.7,"1sd":5.1,"2sd":5.6,"3sd":6.1},{"tinggi":56,"min3sd":3.8,"min2sd":4.1,"min1sd":4.4,"median":4.8,"1sd":5.3,"2sd":5.8,"3sd":6.3},{"tinggi":56.5,"min3sd":3.9,"min2sd":4.2,"min1sd":4.6,"median":5,"1sd":5.4,"2sd":5.9,"3sd":6.5},{"tinggi":57,"min3sd":4,"min2sd":4.3,"min1sd":4.7,"median":5.1,"1sd":5.6,"2sd":6.1,"3sd":6.7},{"tinggi":57.5,"min3sd":4.1,"min2sd":4.5,"min1sd":4.9,"median":5.3,"1sd":5.7,"2sd":6.3,"3sd":6.9},{"tinggi":58,"min3sd":4.3,"min2sd":4.6,"min1sd":5,"median":5.4,"1sd":5.9,"2sd":6.4,"3sd":7.1},{"tinggi":58.5,"min3sd":4.4,"min2sd":4.7,"min1sd":5.1,"median":5.6,"1sd":6.1,"2sd":6.6,"3sd":7.2},{"tinggi":59,"min3sd":4.5,"min2sd":4.8,"min1sd":5.3,"median":5.7,"1sd":6.2,"2sd":6.8,"3sd":7.4},{"tinggi":59.5,"min3sd":4.6,"min2sd":5,"min1sd":5.4,"median":5.9,"1sd":6.4,"2sd":7,"3sd":7.6},{"tinggi":60,"min3sd":4.7,"min2sd":5.1,"min1sd":5.5,"median":6,"1sd":6.5,"2sd":7.1,"3sd":7.8},{"tinggi":60.5,"min3sd":4.8,"min2sd":5.2,"min1sd":5.6,"median":6.1,"1sd":6.7,"2sd":7.3,"3sd":8},{"tinggi":61,"min3sd":4.9,"min2sd":5.3,"min1sd":5.8,"median":6.3,"1sd":6.8,"2sd":7.4,"3sd":8.1},{"tinggi":61.5,"min3sd":5,"min2sd":5.4,"min1sd":5.9,"median":6.4,"1sd":7,"2sd":7.6,"3sd":8.3},{"tinggi":62,"min3sd":5.1,"min2sd":5.6,"min1sd":6,"median":6.5,"1sd":7.1,"2sd":7.7,"3sd":8.5},{"tinggi":62.5,"min3sd":5.2,"min2sd":5.7,"min1sd":6.1,"median":6.7,"1sd":7.2,"2sd":7.9,"3sd":8.6},{"tinggi":63,"min3sd":5.3,"min2sd":5.8,"min1sd":6.2,"median":6.8,"1sd":7.4,"2sd":8,"3sd":8.8},{"tinggi":63.5,"min3sd":5.4,"min2sd":5.9,"min1sd":6.4,"median":6.9,"1sd":7.5,"2sd":8.2,"3sd":8.9},{"tinggi":64,"min3sd":5.5,"min2sd":6,"min1sd":6.5,"median":7,"1sd":7.6,"2sd":8.3,"3sd":9.1},{"tinggi":64.5,"min3sd":5.6,"min2sd":6.1,"min1sd":6.6,"median":7.1,"1sd":7.8,"2sd":8.5,"3sd":9.3},{"tinggi":65,"min3sd":5.7,"min2sd":6.2,"min1sd":6.7,"median":7.3,"1sd":7.9,"2sd":8.6,"3sd":9.4},{"tinggi":65.5,"min3sd":5.8,"min2sd":6.3,"min1sd":6.8,"median":7.4,"1sd":8,"2sd":8.7,"3sd":9.6},{"tinggi":66,"min3sd":5.9,"min2sd":6.4,"min1sd":6.9,"median":7.5,"1sd":8.2,"2sd":8.9,"3sd":9.7},{"tinggi":66.5,"min3sd":6,"min2sd":6.5,"min1sd":7,"median":7.6,"1sd":8.3,"2sd":9,"3sd":9.9},{"tinggi":67,"min3sd":6.1,"min2sd":6.6,"min1sd":7.1,"median":7.7,"1sd":8.4,"2sd":9.2,"3sd":10},{"tinggi":67.5,"min3sd":6.2,"min2sd":6.7,"min1sd":7.2,"median":7.9,"1sd":8.5,"2sd":9.3,"3sd":10.2},{"tinggi":68,"min3sd":6.3,"min2sd":6.8,"min1sd":7.3,"median":8,"1sd":8.7,"2sd":9.4,"3sd":10.3},{"tinggi":68.5,"min3sd":6.4,"min2sd":6.9,"min1sd":7.5,"median":8.1,"1sd":8.8,"2sd":9.6,"3sd":10.5},{"tinggi":69,"min3sd":6.5,"min2sd":7,"min1sd":7.6,"median":8.2,"1sd":8.9,"2sd":9.7,"3sd":10.6},{"tinggi":69.5,"min3sd":6.6,"min2sd":7.1,"min1sd":7.7,"median":8.3,"1sd":9,"2sd":9.8,"3sd":10.8},{"tinggi":70,"min3sd":6.6,"min2sd":7.2,"min1sd":7.8,"median":8.4,"1sd":9.2,"2sd":10,"3sd":10.9},{"tinggi":70.5,"min3sd":6.7,"min2sd":7.3,"min1sd":7.9,"median":8.5,"1sd":9.3,"2sd":10.1,"3sd":11.1},{"tinggi":71,"min3sd":6.8,"min2sd":7.4,"min1sd":8,"median":8.6,"1sd":9.4,"2sd":10.2,"3sd":11.2},{"tinggi":71.5,"min3sd":6.9,"min2sd":7.5,"min1sd":8.1,"median":8.8,"1sd":9.5,"2sd":10.4,"3sd":11.3},{"tinggi":72,"min3sd":7,"min2sd":7.6,"min1sd":8.2,"median":8.9,"1sd":9.6,"2sd":10.5,"3sd":11.5},{"tinggi":72.5,"min3sd":7.1,"min2sd":7.6,"min1sd":8.3,"median":9,"1sd":9.8,"2sd":10.6,"3sd":11.6},{"tinggi":73,"min3sd":7.2,"min2sd":7.7,"min1sd":8.4,"median":9.1,"1sd":9.9,"2sd":10.8,"3sd":11.8},{"tinggi":73.5,"min3sd":7.2,"min2sd":7.8,"min1sd":8.5,"median":9.2,"1sd":10,"2sd":10.9,"3sd":11.9},{"tinggi":74,"min3sd":7.3,"min2sd":7.9,"min1sd":8.6,"median":9.3,"1sd":10.1,"2sd":11,"3sd":12.1},{"tinggi":74.5,"min3sd":7.4,"min2sd":8,"min1sd":8.7,"median":9.4,"1sd":10.2,"2sd":11.2,"3sd":12.2},{"tinggi":75,"min3sd":7.5,"min2sd":8.1,"min1sd":8.8,"median":9.5,"1sd":10.3,"2sd":11.3,"3sd":12.3},{"tinggi":75.5,"min3sd":7.6,"min2sd":8.2,"min1sd":8.8,"median":9.6,"1sd":10.4,"2sd":11.4,"3sd":12.5},{"tinggi":76,"min3sd":7.6,"min2sd":8.3,"min1sd":8.9,"median":9.7,"1sd":10.6,"2sd":11.5,"3sd":12.6},{"tinggi":76.5,"min3sd":7.7,"min2sd":8.3,"min1sd":9,"median":9.8,"1sd":10.7,"2sd":11.6,"3sd":12.7},{"tinggi":77,"min3sd":7.8,"min2sd":8.4,"min1sd":9.1,"median":9.9,"1sd":10.8,"2sd":11.7,"3sd":12.8},{"tinggi":77.5,"min3sd":7.9,"min2sd":8.5,"min1sd":9.2,"median":10,"1sd":10.9,"2sd":11.9,"3sd":13},{"tinggi":78,"min3sd":7.9,"min2sd":8.6,"min1sd":9.3,"median":10.1,"1sd":11,"2sd":12,"3sd":13.1},{"tinggi":78.5,"min3sd":8,"min2sd":8.7,"min1sd":9.4,"median":10.2,"1sd":11.1,"2sd":12.1,"3sd":13.2},{"tinggi":79,"min3sd":8.1,"min2sd":8.7,"min1sd":9.5,"median":10.3,"1sd":11.2,"2sd":12.2,"3sd":13.3},{"tinggi":79.5,"min3sd":8.2,"min2sd":8.8,"min1sd":9.5,"median":10.4,"1sd":11.3,"2sd":12.3,"3sd":13.4},{"tinggi":80,"min3sd":8.2,"min2sd":8.9,"min1sd":9.6,"median":10.4,"1sd":11.4,"2sd":12.4,"3sd":13.6},{"tinggi":80.5,"min3sd":8.3,"min2sd":9,"min1sd":9.7,"median":10.5,"1sd":11.5,"2sd":12.5,"3sd":13.7},{"tinggi":81,"min3sd":8.4,"min2sd":9.1,"min1sd":9.8,"median":10.6,"1sd":11.6,"2sd":12.6,"3sd":13.8},{"tinggi":81.5,"min3sd":8.5,"min2sd":9.1,"min1sd":9.9,"median":10.7,"1sd":11.7,"2sd":12.7,"3sd":13.9},{"tinggi":82,"min3sd":8.5,"min2sd":9.2,"min1sd":10,"median":10.8,"1sd":11.8,"2sd":12.8,"3sd":14},{"tinggi":82.5,"min3sd":8.6,"min2sd":9.3,"min1sd":10.1,"median":10.9,"1sd":11.9,"2sd":13,"3sd":14.2},{"tinggi":83,"min3sd":8.7,"min2sd":9.4,"min1sd":10.2,"median":11,"1sd":12,"2sd":13.1,"3sd":14.3},{"tinggi":83.5,"min3sd":8.8,"min2sd":9.5,"min1sd":10.3,"median":11.2,"1sd":12.1,"2sd":13.2,"3sd":14.4},{"tinggi":84,"min3sd":8.9,"min2sd":9.6,"min1sd":10.4,"median":11.3,"1sd":12.2,"2sd":13.3,"3sd":14.6},{"tinggi":84.5,"min3sd":9,"min2sd":9.7,"min1sd":10.5,"median":11.4,"1sd":12.4,"2sd":13.5,"3sd":14.7},{"tinggi":85,"min3sd":9.1,"min2sd":9.8,"min1sd":10.6,"median":11.5,"1sd":12.5,"2sd":13.6,"3sd":14.9},{"tinggi":85.5,"min3sd":9.2,"min2sd":9.9,"min1sd":10.7,"median":11.6,"1sd":12.6,"2sd":13.7,"3sd":15},{"tinggi":86,"min3sd":9.3,"min2sd":10,"min1sd":10.8,"median":11.7,"1sd":12.8,"2sd":13.9,"3sd":15.2},{"tinggi":86.5,"min3sd":9.4,"min2sd":10.1,"min1sd":11,"median":11.9,"1sd":12.9,"2sd":14,"3sd":15.3},{"tinggi":87,"min3sd":9.5,"min2sd":10.2,"min1sd":11.1,"median":12,"1sd":13,"2sd":14.2,"3sd":15.5},{"tinggi":87.5,"min3sd":9.6,"min2sd":10.4,"min1sd":11.2,"median":12.1,"1sd":13.2,"2sd":14.3,"3sd":15.6},{"tinggi":88,"min3sd":9.7,"min2sd":10.5,"min1sd":11.3,"median":12.2,"1sd":13.3,"2sd":14.5,"3sd":15.8},{"tinggi":88.5,"min3sd":9.8,"min2sd":10.6,"min1sd":11.4,"median":12.4,"1sd":13.4,"2sd":14.6,"3sd":15.9},{"tinggi":89,"min3sd":9.9,"min2sd":10.7,"min1sd":11.5,"median":12.5,"1sd":13.5,"2sd":14.7,"3sd":16.1},{"tinggi":89.5,"min3sd":10,"min2sd":10.8,"min1sd":11.6,"median":12.6,"1sd":13.7,"2sd":14.9,"3sd":16.2},{"tinggi":90,"min3sd":10.1,"min2sd":10.9,"min1sd":11.8,"median":12.7,"1sd":13.8,"2sd":15,"3sd":16.4},{"tinggi":90.5,"min3sd":10.2,"min2sd":11,"min1sd":11.9,"median":12.8,"1sd":13.9,"2sd":15.1,"3sd":16.5},{"tinggi":91,"min3sd":10.3,"min2sd":11.1,"min1sd":12,"median":13,"1sd":14.1,"2sd":15.3,"3sd":16.7},{"tinggi":91.5,"min3sd":10.4,"min2sd":11.2,"min1sd":12.1,"median":13.1,"1sd":14.2,"2sd":15.4,"3sd":16.8},{"tinggi":92,"min3sd":10.5,"min2sd":11.3,"min1sd":12.2,"median":13.2,"1sd":14.3,"2sd":15.6,"3sd":17},{"tinggi":92.5,"min3sd":10.6,"min2sd":11.4,"min1sd":12.3,"median":13.3,"1sd":14.4,"2sd":15.7,"3sd":17.1},{"tinggi":93,"min3sd":10.7,"min2sd":11.5,"min1sd":12.4,"median":13.4,"1sd":14.6,"2sd":15.8,"3sd":17.3},{"tinggi":93.5,"min3sd":10.7,"min2sd":11.6,"min1sd":12.5,"median":13.5,"1sd":14.7,"2sd":16,"3sd":17.4},{"tinggi":94,"min3sd":10.8,"min2sd":11.7,"min1sd":12.6,"median":13.7,"1sd":14.8,"2sd":16.1,"3sd":17.6},{"tinggi":94.5,"min3sd":10.9,"min2sd":11.8,"min1sd":12.7,"median":13.8,"1sd":14.9,"2sd":16.3,"3sd":17.7},{"tinggi":95,"min3sd":11,"min2sd":11.9,"min1sd":12.8,"median":13.9,"1sd":15.1,"2sd":16.4,"3sd":17.9},{"tinggi":95.5,"min3sd":11.1,"min2sd":12,"min1sd":12.9,"median":14,"1sd":15.2,"2sd":16.5,"3sd":18},{"tinggi":96,"min3sd":11.2,"min2sd":12.1,"min1sd":13.1,"median":14.1,"1sd":15.3,"2sd":16.7,"3sd":18.2},{"tinggi":96.5,"min3sd":11.3,"min2sd":12.2,"min1sd":13.2,"median":14.3,"1sd":15.5,"2sd":16.8,"3sd":18.4},{"tinggi":97,"min3sd":11.4,"min2sd":12.3,"min1sd":13.3,"median":14.4,"1sd":15.6,"2sd":17,"3sd":18.5},{"tinggi":97.5,"min3sd":11.5,"min2sd":12.4,"min1sd":13.4,"median":14.5,"1sd":15.7,"2sd":17.1,"3sd":18.7},{"tinggi":98,"min3sd":11.6,"min2sd":12.5,"min1sd":13.5,"median":14.6,"1sd":15.9,"2sd":17.3,"3sd":18.9},{"tinggi":98.5,"min3sd":11.7,"min2sd":12.6,"min1sd":13.6,"median":14.8,"1sd":16,"2sd":17.5,"3sd":19.1},{"tinggi":99,"min3sd":11.8,"min2sd":12.7,"min1sd":13.7,"median":14.9,"1sd":16.2,"2sd":17.6,"3sd":19.2},{"tinggi":99.5,"min3sd":11.9,"min2sd":12.8,"min1sd":13.9,"median":15,"1sd":16.3,"2sd":17.8,"3sd":19.4},{"tinggi":100,"min3sd":12,"min2sd":12.9,"min1sd":14,"median":15.2,"1sd":16.5,"2sd":18,"3sd":19.6},{"tinggi":100.5,"min3sd":12.1,"min2sd":13,"min1sd":14.1,"median":15.3,"1sd":16.6,"2sd":18.1,"3sd":19.8},{"tinggi":101,"min3sd":12.2,"min2sd":13.2,"min1sd":14.2,"median":15.4,"1sd":16.8,"2sd":18.3,"3sd":20},{"tinggi":101.5,"min3sd":12.3,"min2sd":13.3,"min1sd":14.4,"median":15.6,"1sd":16.9,"2sd":18.5,"3sd":20.2},{"tinggi":102,"min3sd":12.4,"min2sd":13.4,"min1sd":14.5,"median":15.7,"1sd":17.1,"2sd":18.7,"3sd":20.4},{"tinggi":102.5,"min3sd":12.5,"min2sd":13.5,"min1sd":14.6,"median":15.9,"1sd":17.3,"2sd":18.8,"3sd":20.6},{"tinggi":103,"min3sd":12.6,"min2sd":13.6,"min1sd":14.8,"median":16,"1sd":17.4,"2sd":19,"3sd":20.8},{"tinggi":103.5,"min3sd":12.7,"min2sd":13.7,"min1sd":14.9,"median":16.2,"1sd":17.6,"2sd":19.2,"3sd":21},{"tinggi":104,"min3sd":12.8,"min2sd":13.9,"min1sd":15,"median":16.3,"1sd":17.8,"2sd":19.4,"3sd":21.2},{"tinggi":104.5,"min3sd":12.9,"min2sd":14,"min1sd":15.2,"median":16.5,"1sd":17.9,"2sd":19.6,"3sd":21.5},{"tinggi":105,"min3sd":13,"min2sd":14.1,"min1sd":15.3,"median":16.6,"1sd":18.1,"2sd":19.8,"3sd":21.7},{"tinggi":105.5,"min3sd":13.2,"min2sd":14.2,"min1sd":15.4,"median":16.8,"1sd":18.3,"2sd":20,"3sd":21.9},{"tinggi":106,"min3sd":13.3,"min2sd":14.4,"min1sd":15.6,"median":16.9,"1sd":18.5,"2sd":20.2,"3sd":22.1},{"tinggi":106.5,"min3sd":13.4,"min2sd":14.5,"min1sd":15.7,"median":17.1,"1sd":18.6,"2sd":20.4,"3sd":22.4},{"tinggi":107,"min3sd":13.5,"min2sd":14.6,"min1sd":15.9,"median":17.3,"1sd":18.8,"2sd":20.6,"3sd":22.6},{"tinggi":107.5,"min3sd":13.6,"min2sd":14.7,"min1sd":16,"median":17.4,"1sd":19,"2sd":20.8,"3sd":22.8},{"tinggi":108,"min3sd":13.7,"min2sd":14.9,"min1sd":16.2,"median":17.6,"1sd":19.2,"2sd":21,"3sd":23.1},{"tinggi":108.5,"min3sd":13.8,"min2sd":15,"min1sd":16.3,"median":17.8,"1sd":19.4,"2sd":21.2,"3sd":23.3},{"tinggi":109,"min3sd":14,"min2sd":15.1,"min1sd":16.5,"median":17.9,"1sd":19.6,"2sd":21.4,"3sd":23.6},{"tinggi":109.5,"min3sd":14.1,"min2sd":15.3,"min1sd":16.6,"median":18.1,"1sd":19.8,"2sd":21.7,"3sd":23.8},{"tinggi":110,"min3sd":14.2,"min2sd":15.4,"min1sd":16.8,"median":18.3,"1sd":20,"2sd":21.9,"3sd":24.1}]', true);

        return $data;
    }

    public static function table_bb_tb_2460_laki_laki()
    {
        $data=json_decode('[{"tinggi":65,"min3sd":5.9,"min2sd":6.3,"min1sd":6.9,"median":7.4,"1sd":8.1,"2sd":8.8,"3sd":9.6},{"tinggi":65.5,"min3sd":6,"min2sd":6.4,"min1sd":7,"median":7.6,"1sd":8.2,"2sd":8.9,"3sd":9.8},{"tinggi":66,"min3sd":6.1,"min2sd":6.5,"min1sd":7.1,"median":7.7,"1sd":8.3,"2sd":9.1,"3sd":9.9},{"tinggi":66.5,"min3sd":6.1,"min2sd":6.6,"min1sd":7.2,"median":7.8,"1sd":8.5,"2sd":9.2,"3sd":10.1},{"tinggi":67,"min3sd":6.2,"min2sd":6.7,"min1sd":7.3,"median":7.9,"1sd":8.6,"2sd":9.4,"3sd":10.2},{"tinggi":67.5,"min3sd":6.3,"min2sd":6.8,"min1sd":7.4,"median":8,"1sd":8.7,"2sd":9.5,"3sd":10.4},{"tinggi":68,"min3sd":6.4,"min2sd":6.9,"min1sd":7.5,"median":8.1,"1sd":8.8,"2sd":9.6,"3sd":10.5},{"tinggi":68.5,"min3sd":6.5,"min2sd":7,"min1sd":7.6,"median":8.2,"1sd":9,"2sd":9.8,"3sd":10.7},{"tinggi":69,"min3sd":6.6,"min2sd":7.1,"min1sd":7.7,"median":8.4,"1sd":9.1,"2sd":9.9,"3sd":10.8},{"tinggi":69.5,"min3sd":6.7,"min2sd":7.2,"min1sd":7.8,"median":8.5,"1sd":9.2,"2sd":10,"3sd":11},{"tinggi":70,"min3sd":6.8,"min2sd":7.3,"min1sd":7.9,"median":8.6,"1sd":9.3,"2sd":10.2,"3sd":11.1},{"tinggi":70.5,"min3sd":6.9,"min2sd":7.4,"min1sd":8,"median":8.7,"1sd":9.5,"2sd":10.3,"3sd":11.3},{"tinggi":71,"min3sd":6.9,"min2sd":7.5,"min1sd":8.1,"median":8.8,"1sd":9.6,"2sd":10.4,"3sd":11.4},{"tinggi":71.5,"min3sd":7,"min2sd":7.6,"min1sd":8.2,"median":8.9,"1sd":9.7,"2sd":10.6,"3sd":11.6},{"tinggi":72,"min3sd":7.1,"min2sd":7.7,"min1sd":8.3,"median":9,"1sd":9.8,"2sd":10.7,"3sd":11.7},{"tinggi":72.5,"min3sd":7.2,"min2sd":7.8,"min1sd":8.4,"median":9.1,"1sd":9.9,"2sd":10.8,"3sd":11.8},{"tinggi":73,"min3sd":7.3,"min2sd":7.9,"min1sd":8.5,"median":9.2,"1sd":10,"2sd":11,"3sd":12},{"tinggi":73.5,"min3sd":7.4,"min2sd":7.9,"min1sd":8.6,"median":9.3,"1sd":10.2,"2sd":11.1,"3sd":12.1},{"tinggi":74,"min3sd":7.4,"min2sd":8,"min1sd":8.7,"median":9.4,"1sd":10.3,"2sd":11.2,"3sd":12.2},{"tinggi":74.5,"min3sd":7.5,"min2sd":8.1,"min1sd":8.8,"median":9.5,"1sd":10.4,"2sd":11.3,"3sd":12.4},{"tinggi":75,"min3sd":7.6,"min2sd":8.2,"min1sd":8.9,"median":9.6,"1sd":10.5,"2sd":11.4,"3sd":12.5},{"tinggi":75.5,"min3sd":7.7,"min2sd":8.3,"min1sd":9,"median":9.7,"1sd":10.6,"2sd":11.6,"3sd":12.6},{"tinggi":76,"min3sd":7.7,"min2sd":8.4,"min1sd":9.1,"median":9.8,"1sd":10.7,"2sd":11.7,"3sd":12.8},{"tinggi":76.5,"min3sd":7.8,"min2sd":8.5,"min1sd":9.2,"median":9.9,"1sd":10.8,"2sd":11.8,"3sd":12.9},{"tinggi":77,"min3sd":7.9,"min2sd":8.5,"min1sd":9.2,"median":10,"1sd":10.9,"2sd":11.9,"3sd":13},{"tinggi":77.5,"min3sd":8,"min2sd":8.6,"min1sd":9.3,"median":10.1,"1sd":11,"2sd":12,"3sd":13.1},{"tinggi":78,"min3sd":8,"min2sd":8.7,"min1sd":9.4,"median":10.2,"1sd":11.1,"2sd":12.1,"3sd":13.3},{"tinggi":78.5,"min3sd":8.1,"min2sd":8.8,"min1sd":9.5,"median":10.3,"1sd":11.2,"2sd":12.2,"3sd":13.4},{"tinggi":79,"min3sd":8.2,"min2sd":8.8,"min1sd":9.6,"median":10.4,"1sd":11.3,"2sd":12.3,"3sd":13.5},{"tinggi":79.5,"min3sd":8.3,"min2sd":8.9,"min1sd":9.7,"median":10.5,"1sd":11.4,"2sd":12.4,"3sd":13.6},{"tinggi":80,"min3sd":8.3,"min2sd":9,"min1sd":9.7,"median":10.6,"1sd":11.5,"2sd":12.6,"3sd":13.7},{"tinggi":80.5,"min3sd":8.4,"min2sd":9.1,"min1sd":9.8,"median":10.7,"1sd":11.6,"2sd":12.7,"3sd":13.8},{"tinggi":81,"min3sd":8.5,"min2sd":9.2,"min1sd":9.9,"median":10.8,"1sd":11.7,"2sd":12.8,"3sd":14},{"tinggi":81.5,"min3sd":8.6,"min2sd":9.3,"min1sd":10,"median":10.9,"1sd":11.8,"2sd":12.9,"3sd":14.1},{"tinggi":82,"min3sd":8.7,"min2sd":9.3,"min1sd":10.1,"median":11,"1sd":11.9,"2sd":13,"3sd":14.2},{"tinggi":82.5,"min3sd":8.7,"min2sd":9.4,"min1sd":10.2,"median":11.1,"1sd":12.1,"2sd":13.1,"3sd":14.4},{"tinggi":83,"min3sd":8.8,"min2sd":9.5,"min1sd":10.3,"median":11.2,"1sd":12.2,"2sd":13.3,"3sd":14.5},{"tinggi":83.5,"min3sd":8.9,"min2sd":9.6,"min1sd":10.4,"median":11.3,"1sd":12.3,"2sd":13.4,"3sd":14.6},{"tinggi":84,"min3sd":9,"min2sd":9.7,"min1sd":10.5,"median":11.4,"1sd":12.4,"2sd":13.5,"3sd":14.8},{"tinggi":84.5,"min3sd":9.1,"min2sd":9.9,"min1sd":10.7,"median":11.5,"1sd":12.5,"2sd":13.7,"3sd":14.9},{"tinggi":85,"min3sd":9.2,"min2sd":10,"min1sd":10.8,"median":11.7,"1sd":12.7,"2sd":13.8,"3sd":15.1},{"tinggi":85.5,"min3sd":9.3,"min2sd":10.1,"min1sd":10.9,"median":11.8,"1sd":12.8,"2sd":13.9,"3sd":15.2},{"tinggi":86,"min3sd":9.4,"min2sd":10.2,"min1sd":11,"median":11.9,"1sd":12.9,"2sd":14.1,"3sd":15.4},{"tinggi":86.5,"min3sd":9.5,"min2sd":10.3,"min1sd":11.1,"median":12,"1sd":13.1,"2sd":14.2,"3sd":15.5},{"tinggi":87,"min3sd":9.6,"min2sd":10.4,"min1sd":11.2,"median":12.2,"1sd":13.2,"2sd":14.4,"3sd":15.7},{"tinggi":87.5,"min3sd":9.7,"min2sd":10.5,"min1sd":11.3,"median":12.3,"1sd":13.3,"2sd":14.5,"3sd":15.8},{"tinggi":88,"min3sd":9.8,"min2sd":10.6,"min1sd":11.5,"median":12.4,"1sd":13.5,"2sd":14.7,"3sd":16},{"tinggi":88.5,"min3sd":9.9,"min2sd":10.7,"min1sd":11.6,"median":12.5,"1sd":13.6,"2sd":14.8,"3sd":16.1},{"tinggi":89,"min3sd":10,"min2sd":10.8,"min1sd":11.7,"median":12.6,"1sd":13.7,"2sd":14.9,"3sd":16.3},{"tinggi":89.5,"min3sd":10.1,"min2sd":10.9,"min1sd":11.8,"median":12.8,"1sd":13.9,"2sd":15.1,"3sd":16.4},{"tinggi":90,"min3sd":10.2,"min2sd":11,"min1sd":11.9,"median":12.9,"1sd":14,"2sd":15.2,"3sd":16.6},{"tinggi":90.5,"min3sd":10.3,"min2sd":11.1,"min1sd":12,"median":13,"1sd":14.1,"2sd":15.3,"3sd":16.7},{"tinggi":91,"min3sd":10.4,"min2sd":11.2,"min1sd":12.1,"median":13.1,"1sd":14.2,"2sd":15.5,"3sd":16.9},{"tinggi":91.5,"min3sd":10.5,"min2sd":11.3,"min1sd":12.2,"median":13.2,"1sd":14.4,"2sd":15.6,"3sd":17},{"tinggi":92,"min3sd":10.6,"min2sd":11.4,"min1sd":12.3,"median":13.4,"1sd":14.5,"2sd":15.8,"3sd":17.2},{"tinggi":92.5,"min3sd":10.7,"min2sd":11.5,"min1sd":12.4,"median":13.5,"1sd":14.6,"2sd":15.9,"3sd":17.3},{"tinggi":93,"min3sd":10.8,"min2sd":11.6,"min1sd":12.6,"median":13.6,"1sd":14.7,"2sd":16,"3sd":17.5},{"tinggi":93.5,"min3sd":10.9,"min2sd":11.7,"min1sd":12.7,"median":13.7,"1sd":14.9,"2sd":16.2,"3sd":17.6},{"tinggi":94,"min3sd":11,"min2sd":11.8,"min1sd":12.8,"median":13.8,"1sd":15,"2sd":16.3,"3sd":17.8},{"tinggi":94.5,"min3sd":11.1,"min2sd":11.9,"min1sd":12.9,"median":13.9,"1sd":15.1,"2sd":16.5,"3sd":17.9},{"tinggi":95,"min3sd":11.1,"min2sd":12,"min1sd":13,"median":14.1,"1sd":15.3,"2sd":16.6,"3sd":18.1},{"tinggi":95.5,"min3sd":11.2,"min2sd":12.1,"min1sd":13.1,"median":14.2,"1sd":15.4,"2sd":16.7,"3sd":18.3},{"tinggi":96,"min3sd":11.3,"min2sd":12.2,"min1sd":13.2,"median":14.3,"1sd":15.5,"2sd":16.9,"3sd":18.4},{"tinggi":96.5,"min3sd":11.4,"min2sd":12.3,"min1sd":13.3,"median":14.4,"1sd":15.7,"2sd":17,"3sd":18.6},{"tinggi":97,"min3sd":11.5,"min2sd":12.4,"min1sd":13.4,"median":14.6,"1sd":15.8,"2sd":17.2,"3sd":18.8},{"tinggi":97.5,"min3sd":11.6,"min2sd":12.5,"min1sd":13.6,"median":14.7,"1sd":15.9,"2sd":17.4,"3sd":18.9},{"tinggi":98,"min3sd":11.7,"min2sd":12.6,"min1sd":13.7,"median":14.8,"1sd":16.1,"2sd":17.5,"3sd":19.1},{"tinggi":98.5,"min3sd":11.8,"min2sd":12.8,"min1sd":13.8,"median":14.9,"1sd":16.2,"2sd":17.7,"3sd":19.3},{"tinggi":99,"min3sd":11.9,"min2sd":12.9,"min1sd":13.9,"median":15.1,"1sd":16.4,"2sd":17.9,"3sd":19.5},{"tinggi":99.5,"min3sd":12,"min2sd":13,"min1sd":14,"median":15.2,"1sd":16.5,"2sd":18,"3sd":19.7},{"tinggi":100,"min3sd":12.1,"min2sd":13.1,"min1sd":14.2,"median":15.4,"1sd":16.7,"2sd":18.2,"3sd":19.9},{"tinggi":100.5,"min3sd":12.2,"min2sd":13.2,"min1sd":14.3,"median":15.5,"1sd":16.9,"2sd":18.4,"3sd":20.1},{"tinggi":101,"min3sd":12.3,"min2sd":13.3,"min1sd":14.4,"median":15.6,"1sd":17,"2sd":18.5,"3sd":20.3},{"tinggi":101.5,"min3sd":12.4,"min2sd":13.4,"min1sd":14.5,"median":15.8,"1sd":17.2,"2sd":18.7,"3sd":20.5},{"tinggi":102,"min3sd":12.5,"min2sd":13.6,"min1sd":14.7,"median":15.9,"1sd":17.3,"2sd":18.9,"3sd":20.7},{"tinggi":102.5,"min3sd":12.6,"min2sd":13.7,"min1sd":14.8,"median":16.1,"1sd":17.5,"2sd":19.1,"3sd":20.9},{"tinggi":103,"min3sd":12.8,"min2sd":13.8,"min1sd":14.9,"median":16.2,"1sd":17.7,"2sd":19.3,"3sd":21.1},{"tinggi":103.5,"min3sd":12.9,"min2sd":13.9,"min1sd":15.1,"median":16.4,"1sd":17.8,"2sd":19.5,"3sd":21.3},{"tinggi":104,"min3sd":13,"min2sd":14,"min1sd":15.2,"median":16.5,"1sd":18,"2sd":19.7,"3sd":21.6},{"tinggi":104.5,"min3sd":13.1,"min2sd":14.2,"min1sd":15.4,"median":16.7,"1sd":18.2,"2sd":19.9,"3sd":21.8},{"tinggi":105,"min3sd":13.2,"min2sd":14.3,"min1sd":15.5,"median":16.8,"1sd":18.4,"2sd":20.1,"3sd":22},{"tinggi":105.5,"min3sd":13.3,"min2sd":14.4,"min1sd":15.6,"median":17,"1sd":18.5,"2sd":20.3,"3sd":22.2},{"tinggi":106,"min3sd":13.4,"min2sd":14.5,"min1sd":15.8,"median":17.2,"1sd":18.7,"2sd":20.5,"3sd":22.5},{"tinggi":106.5,"min3sd":13.5,"min2sd":14.7,"min1sd":15.9,"median":17.3,"1sd":18.9,"2sd":20.7,"3sd":22.7},{"tinggi":107,"min3sd":13.7,"min2sd":14.8,"min1sd":16.1,"median":17.5,"1sd":19.1,"2sd":20.9,"3sd":22.9},{"tinggi":107.5,"min3sd":13.8,"min2sd":14.9,"min1sd":16.2,"median":17.7,"1sd":19.3,"2sd":21.1,"3sd":23.2},{"tinggi":108,"min3sd":13.9,"min2sd":15.1,"min1sd":16.4,"median":17.8,"1sd":19.5,"2sd":21.3,"3sd":23.4},{"tinggi":108.5,"min3sd":14,"min2sd":15.2,"min1sd":16.5,"median":18,"1sd":19.7,"2sd":21.5,"3sd":23.7},{"tinggi":109,"min3sd":14.1,"min2sd":15.3,"min1sd":16.7,"median":18.2,"1sd":19.8,"2sd":21.8,"3sd":23.9},{"tinggi":109.5,"min3sd":14.3,"min2sd":15.5,"min1sd":16.8,"median":18.3,"1sd":20,"2sd":22,"3sd":24.2},{"tinggi":110,"min3sd":14.4,"min2sd":15.6,"min1sd":17,"median":18.5,"1sd":20.2,"2sd":22.2,"3sd":24.4},{"tinggi":110.5,"min3sd":14.5,"min2sd":15.8,"min1sd":17.1,"median":18.7,"1sd":20.4,"2sd":22.4,"3sd":24.7},{"tinggi":111,"min3sd":14.6,"min2sd":15.9,"min1sd":17.3,"median":18.9,"1sd":20.7,"2sd":22.7,"3sd":25},{"tinggi":111.5,"min3sd":14.8,"min2sd":16,"min1sd":17.5,"median":19.1,"1sd":20.9,"2sd":22.9,"3sd":25.2},{"tinggi":112,"min3sd":14.9,"min2sd":16.2,"min1sd":17.6,"median":19.2,"1sd":21.1,"2sd":23.1,"3sd":25.5},{"tinggi":112.5,"min3sd":15,"min2sd":16.3,"min1sd":17.8,"median":19.4,"1sd":21.3,"2sd":23.4,"3sd":25.8},{"tinggi":113,"min3sd":15.2,"min2sd":16.5,"min1sd":18,"median":19.6,"1sd":21.5,"2sd":23.6,"3sd":26},{"tinggi":113.5,"min3sd":15.3,"min2sd":16.6,"min1sd":18.1,"median":19.8,"1sd":21.7,"2sd":23.9,"3sd":26.3},{"tinggi":114,"min3sd":15.4,"min2sd":16.8,"min1sd":18.3,"median":20,"1sd":21.9,"2sd":24.1,"3sd":26.6},{"tinggi":114.5,"min3sd":15.6,"min2sd":16.9,"min1sd":18.5,"median":20.2,"1sd":22.1,"2sd":24.4,"3sd":26.9},{"tinggi":115,"min3sd":15.7,"min2sd":17.1,"min1sd":18.6,"median":20.4,"1sd":22.4,"2sd":24.6,"3sd":27.2},{"tinggi":115.5,"min3sd":15.8,"min2sd":17.2,"min1sd":18.8,"median":20.6,"1sd":22.6,"2sd":24.9,"3sd":27.5},{"tinggi":116,"min3sd":16,"min2sd":17.4,"min1sd":19,"median":20.8,"1sd":22.8,"2sd":25.1,"3sd":27.8},{"tinggi":116.5,"min3sd":16.1,"min2sd":17.5,"min1sd":19.2,"median":21,"1sd":23,"2sd":25.4,"3sd":28},{"tinggi":117,"min3sd":16.2,"min2sd":17.7,"min1sd":19.3,"median":21.2,"1sd":23.3,"2sd":25.6,"3sd":28.3},{"tinggi":117.5,"min3sd":16.4,"min2sd":17.9,"min1sd":19.5,"median":21.4,"1sd":23.5,"2sd":25.9,"3sd":28.6},{"tinggi":118,"min3sd":16.5,"min2sd":18,"min1sd":19.7,"median":21.6,"1sd":23.7,"2sd":26.1,"3sd":28.9},{"tinggi":118.5,"min3sd":16.7,"min2sd":18.2,"min1sd":19.9,"median":21.8,"1sd":23.9,"2sd":26.4,"3sd":29.2},{"tinggi":119,"min3sd":16.8,"min2sd":18.3,"min1sd":20,"median":22,"1sd":24.1,"2sd":26.6,"3sd":29.5},{"tinggi":119.5,"min3sd":16.9,"min2sd":18.5,"min1sd":20.2,"median":22.2,"1sd":24.4,"2sd":26.9,"3sd":29.8},{"tinggi":120,"min3sd":17.1,"min2sd":18.6,"min1sd":20.4,"median":22.4,"1sd":24.6,"2sd":27.2,"3sd":30.1}]', true);

        return $data;
    }

    public static function table_bb_tb_024_perempuan()
    {
        $data=json_decode('[{"tinggi":45,"min3sd":1.9,"min2sd":2.1,"min1sd":2.3,"median":2.5,"1sd":2.7,"2sd":3,"3sd":3.3},{"tinggi":45.5,"min3sd":2,"min2sd":2.1,"min1sd":2.3,"median":2.5,"1sd":2.8,"2sd":3.1,"3sd":3.4},{"tinggi":46,"min3sd":2,"min2sd":2.2,"min1sd":2.4,"median":2.6,"1sd":2.9,"2sd":3.2,"3sd":3.5},{"tinggi":46.5,"min3sd":2.1,"min2sd":2.3,"min1sd":2.5,"median":2.7,"1sd":3,"2sd":3.3,"3sd":3.6},{"tinggi":47,"min3sd":2.2,"min2sd":2.4,"min1sd":2.6,"median":2.8,"1sd":3.1,"2sd":3.4,"3sd":3.7},{"tinggi":47.5,"min3sd":2.2,"min2sd":2.4,"min1sd":2.6,"median":2.9,"1sd":3.2,"2sd":3.5,"3sd":3.8},{"tinggi":48,"min3sd":2.3,"min2sd":2.5,"min1sd":2.7,"median":3,"1sd":3.3,"2sd":3.6,"3sd":4},{"tinggi":48.5,"min3sd":2.4,"min2sd":2.6,"min1sd":2.8,"median":3.1,"1sd":3.4,"2sd":3.7,"3sd":4.1},{"tinggi":49,"min3sd":2.4,"min2sd":2.6,"min1sd":2.9,"median":3.2,"1sd":3.5,"2sd":3.8,"3sd":4.2},{"tinggi":49.5,"min3sd":2.5,"min2sd":2.7,"min1sd":3,"median":3.3,"1sd":3.6,"2sd":3.9,"3sd":4.3},{"tinggi":50,"min3sd":2.6,"min2sd":2.8,"min1sd":3.1,"median":3.4,"1sd":3.7,"2sd":4,"3sd":4.5},{"tinggi":50.5,"min3sd":2.7,"min2sd":2.9,"min1sd":3.2,"median":3.5,"1sd":3.8,"2sd":4.2,"3sd":4.6},{"tinggi":51,"min3sd":2.8,"min2sd":3,"min1sd":3.3,"median":3.6,"1sd":3.9,"2sd":4.3,"3sd":4.8},{"tinggi":51.5,"min3sd":2.8,"min2sd":3.1,"min1sd":3.4,"median":3.7,"1sd":4,"2sd":4.4,"3sd":4.9},{"tinggi":52,"min3sd":2.9,"min2sd":3.2,"min1sd":3.5,"median":3.8,"1sd":4.2,"2sd":4.6,"3sd":5.1},{"tinggi":52.5,"min3sd":3,"min2sd":3.3,"min1sd":3.6,"median":3.9,"1sd":4.3,"2sd":4.7,"3sd":5.2},{"tinggi":53,"min3sd":3.1,"min2sd":3.4,"min1sd":3.7,"median":4,"1sd":4.4,"2sd":4.9,"3sd":5.4},{"tinggi":53.5,"min3sd":3.2,"min2sd":3.5,"min1sd":3.8,"median":4.2,"1sd":4.6,"2sd":5,"3sd":5.5},{"tinggi":54,"min3sd":3.3,"min2sd":3.6,"min1sd":3.9,"median":4.3,"1sd":4.7,"2sd":5.2,"3sd":5.7},{"tinggi":54.5,"min3sd":3.4,"min2sd":3.7,"min1sd":4,"median":4.4,"1sd":4.8,"2sd":5.3,"3sd":5.9},{"tinggi":55,"min3sd":3.5,"min2sd":3.8,"min1sd":4.2,"median":4.5,"1sd":5,"2sd":5.5,"3sd":6.1},{"tinggi":55.5,"min3sd":3.6,"min2sd":3.9,"min1sd":4.3,"median":4.7,"1sd":5.1,"2sd":5.7,"3sd":6.3},{"tinggi":56,"min3sd":3.7,"min2sd":4,"min1sd":4.4,"median":4.8,"1sd":5.3,"2sd":5.8,"3sd":6.4},{"tinggi":56.5,"min3sd":3.8,"min2sd":4.1,"min1sd":4.5,"median":5,"1sd":5.4,"2sd":6,"3sd":6.6},{"tinggi":57,"min3sd":3.9,"min2sd":4.3,"min1sd":4.6,"median":5.1,"1sd":5.6,"2sd":6.1,"3sd":6.8},{"tinggi":57.5,"min3sd":4,"min2sd":4.4,"min1sd":4.8,"median":5.2,"1sd":5.7,"2sd":6.3,"3sd":7},{"tinggi":58,"min3sd":4.1,"min2sd":4.5,"min1sd":4.9,"median":5.4,"1sd":5.9,"2sd":6.5,"3sd":7.1},{"tinggi":58.5,"min3sd":4.2,"min2sd":4.6,"min1sd":5,"median":5.5,"1sd":6,"2sd":6.6,"3sd":7.3},{"tinggi":59,"min3sd":4.3,"min2sd":4.7,"min1sd":5.1,"median":5.6,"1sd":6.2,"2sd":6.8,"3sd":7.5},{"tinggi":59.5,"min3sd":4.4,"min2sd":4.8,"min1sd":5.3,"median":5.7,"1sd":6.3,"2sd":6.9,"3sd":7.7},{"tinggi":60,"min3sd":4.5,"min2sd":4.9,"min1sd":5.4,"median":5.9,"1sd":6.4,"2sd":7.1,"3sd":7.8},{"tinggi":60.5,"min3sd":4.6,"min2sd":5,"min1sd":5.5,"median":6,"1sd":6.6,"2sd":7.3,"3sd":8},{"tinggi":61,"min3sd":4.7,"min2sd":5.1,"min1sd":5.6,"median":6.1,"1sd":6.7,"2sd":7.4,"3sd":8.2},{"tinggi":61.5,"min3sd":4.8,"min2sd":5.2,"min1sd":5.7,"median":6.3,"1sd":6.9,"2sd":7.6,"3sd":8.4},{"tinggi":62,"min3sd":4.9,"min2sd":5.3,"min1sd":5.8,"median":6.4,"1sd":7,"2sd":7.7,"3sd":8.5},{"tinggi":62.5,"min3sd":5,"min2sd":5.4,"min1sd":5.9,"median":6.5,"1sd":7.1,"2sd":7.8,"3sd":8.7},{"tinggi":63,"min3sd":5.1,"min2sd":5.5,"min1sd":6,"median":6.6,"1sd":7.3,"2sd":8,"3sd":8.8},{"tinggi":63.5,"min3sd":5.2,"min2sd":5.6,"min1sd":6.2,"median":6.7,"1sd":7.4,"2sd":8.1,"3sd":9},{"tinggi":64,"min3sd":5.3,"min2sd":5.7,"min1sd":6.3,"median":6.9,"1sd":7.5,"2sd":8.3,"3sd":9.1},{"tinggi":64.5,"min3sd":5.4,"min2sd":5.8,"min1sd":6.4,"median":7,"1sd":7.6,"2sd":8.4,"3sd":9.3},{"tinggi":65,"min3sd":5.5,"min2sd":5.9,"min1sd":6.5,"median":7.1,"1sd":7.8,"2sd":8.6,"3sd":9.5},{"tinggi":65.5,"min3sd":5.5,"min2sd":6,"min1sd":6.6,"median":7.2,"1sd":7.9,"2sd":8.7,"3sd":9.6},{"tinggi":66,"min3sd":5.6,"min2sd":6.1,"min1sd":6.7,"median":7.3,"1sd":8,"2sd":8.8,"3sd":9.8},{"tinggi":66.5,"min3sd":5.7,"min2sd":6.2,"min1sd":6.8,"median":7.4,"1sd":8.1,"2sd":9,"3sd":9.9},{"tinggi":67,"min3sd":5.8,"min2sd":6.3,"min1sd":6.9,"median":7.5,"1sd":8.3,"2sd":9.1,"3sd":10},{"tinggi":67.5,"min3sd":5.9,"min2sd":6.4,"min1sd":7,"median":7.6,"1sd":8.4,"2sd":9.2,"3sd":10.2},{"tinggi":68,"min3sd":6,"min2sd":6.5,"min1sd":7.1,"median":7.7,"1sd":8.5,"2sd":9.4,"3sd":10.3},{"tinggi":68.5,"min3sd":6.1,"min2sd":6.6,"min1sd":7.2,"median":7.9,"1sd":8.6,"2sd":9.5,"3sd":10.5},{"tinggi":69,"min3sd":6.1,"min2sd":6.7,"min1sd":7.3,"median":8,"1sd":8.7,"2sd":9.6,"3sd":10.6},{"tinggi":69.5,"min3sd":6.2,"min2sd":6.8,"min1sd":7.4,"median":8.1,"1sd":8.8,"2sd":9.7,"3sd":10.7},{"tinggi":70,"min3sd":6.3,"min2sd":6.9,"min1sd":7.5,"median":8.2,"1sd":9,"2sd":9.9,"3sd":10.9},{"tinggi":70.5,"min3sd":6.4,"min2sd":6.9,"min1sd":7.6,"median":8.3,"1sd":9.1,"2sd":10,"3sd":11},{"tinggi":71,"min3sd":6.5,"min2sd":7,"min1sd":7.7,"median":8.4,"1sd":9.2,"2sd":10.1,"3sd":11.1},{"tinggi":71.5,"min3sd":6.5,"min2sd":7.1,"min1sd":7.7,"median":8.5,"1sd":9.3,"2sd":10.2,"3sd":11.3},{"tinggi":72,"min3sd":6.6,"min2sd":7.2,"min1sd":7.8,"median":8.6,"1sd":9.4,"2sd":10.3,"3sd":11.4},{"tinggi":72.5,"min3sd":6.7,"min2sd":7.3,"min1sd":7.9,"median":8.7,"1sd":9.5,"2sd":10.5,"3sd":11.5},{"tinggi":73,"min3sd":6.8,"min2sd":7.4,"min1sd":8,"median":8.8,"1sd":9.6,"2sd":10.6,"3sd":11.7},{"tinggi":73.5,"min3sd":6.9,"min2sd":7.4,"min1sd":8.1,"median":8.9,"1sd":9.7,"2sd":10.7,"3sd":11.8},{"tinggi":74,"min3sd":6.9,"min2sd":7.5,"min1sd":8.2,"median":9,"1sd":9.8,"2sd":10.8,"3sd":11.9},{"tinggi":74.5,"min3sd":7,"min2sd":7.6,"min1sd":8.3,"median":9.1,"1sd":9.9,"2sd":10.9,"3sd":12},{"tinggi":75,"min3sd":7.1,"min2sd":7.7,"min1sd":8.4,"median":9.1,"1sd":10,"2sd":11,"3sd":12.2},{"tinggi":75.5,"min3sd":7.1,"min2sd":7.8,"min1sd":8.5,"median":9.2,"1sd":10.1,"2sd":11.1,"3sd":12.3},{"tinggi":76,"min3sd":7.2,"min2sd":7.8,"min1sd":8.5,"median":9.3,"1sd":10.2,"2sd":11.2,"3sd":12.4},{"tinggi":76.5,"min3sd":7.3,"min2sd":7.9,"min1sd":8.6,"median":9.4,"1sd":10.3,"2sd":11.4,"3sd":12.5},{"tinggi":77,"min3sd":7.4,"min2sd":8,"min1sd":8.7,"median":9.5,"1sd":10.4,"2sd":11.5,"3sd":12.6},{"tinggi":77.5,"min3sd":7.4,"min2sd":8.1,"min1sd":8.8,"median":9.6,"1sd":10.5,"2sd":11.6,"3sd":12.8},{"tinggi":78,"min3sd":7.5,"min2sd":8.2,"min1sd":8.9,"median":9.7,"1sd":10.6,"2sd":11.7,"3sd":12.9},{"tinggi":78.5,"min3sd":7.6,"min2sd":8.2,"min1sd":9,"median":9.8,"1sd":10.7,"2sd":11.8,"3sd":13},{"tinggi":79,"min3sd":7.7,"min2sd":8.3,"min1sd":9.1,"median":9.9,"1sd":10.8,"2sd":11.9,"3sd":13.1},{"tinggi":79.5,"min3sd":7.7,"min2sd":8.4,"min1sd":9.1,"median":10,"1sd":10.9,"2sd":12,"3sd":13.3},{"tinggi":80,"min3sd":7.8,"min2sd":8.5,"min1sd":9.2,"median":10.1,"1sd":11,"2sd":12.1,"3sd":13.4},{"tinggi":80.5,"min3sd":7.9,"min2sd":8.6,"min1sd":9.3,"median":10.2,"1sd":11.2,"2sd":12.3,"3sd":13.5},{"tinggi":81,"min3sd":8,"min2sd":8.7,"min1sd":9.4,"median":10.3,"1sd":11.3,"2sd":12.4,"3sd":13.7},{"tinggi":81.5,"min3sd":8.1,"min2sd":8.8,"min1sd":9.5,"median":10.4,"1sd":11.4,"2sd":12.5,"3sd":13.8},{"tinggi":82,"min3sd":8.1,"min2sd":8.8,"min1sd":9.6,"median":10.5,"1sd":11.5,"2sd":12.6,"3sd":13.9},{"tinggi":82.5,"min3sd":8.2,"min2sd":8.9,"min1sd":9.7,"median":10.6,"1sd":11.6,"2sd":12.8,"3sd":14.1},{"tinggi":83,"min3sd":8.3,"min2sd":9,"min1sd":9.8,"median":10.7,"1sd":11.8,"2sd":12.9,"3sd":14.2},{"tinggi":83.5,"min3sd":8.4,"min2sd":9.1,"min1sd":9.9,"median":10.9,"1sd":11.9,"2sd":13.1,"3sd":14.4},{"tinggi":84,"min3sd":8.5,"min2sd":9.2,"min1sd":10.1,"median":11,"1sd":12,"2sd":13.2,"3sd":14.5},{"tinggi":84.5,"min3sd":8.6,"min2sd":9.3,"min1sd":10.2,"median":11.1,"1sd":12.1,"2sd":13.3,"3sd":14.7},{"tinggi":85,"min3sd":8.7,"min2sd":9.4,"min1sd":10.3,"median":11.2,"1sd":12.3,"2sd":13.5,"3sd":14.9},{"tinggi":85.5,"min3sd":8.8,"min2sd":9.5,"min1sd":10.4,"median":11.3,"1sd":12.4,"2sd":13.6,"3sd":15},{"tinggi":86,"min3sd":8.9,"min2sd":9.7,"min1sd":10.5,"median":11.5,"1sd":12.6,"2sd":13.8,"3sd":15.2},{"tinggi":86.5,"min3sd":9,"min2sd":9.8,"min1sd":10.6,"median":11.6,"1sd":12.7,"2sd":13.9,"3sd":15.4},{"tinggi":87,"min3sd":9.1,"min2sd":9.9,"min1sd":10.7,"median":11.7,"1sd":12.8,"2sd":14.1,"3sd":15.5},{"tinggi":87.5,"min3sd":9.2,"min2sd":10,"min1sd":10.9,"median":11.8,"1sd":13,"2sd":14.2,"3sd":15.7},{"tinggi":88,"min3sd":9.3,"min2sd":10.1,"min1sd":11,"median":12,"1sd":13.1,"2sd":14.4,"3sd":15.9},{"tinggi":88.5,"min3sd":9.4,"min2sd":10.2,"min1sd":11.1,"median":12.1,"1sd":13.2,"2sd":14.5,"3sd":16},{"tinggi":89,"min3sd":9.5,"min2sd":10.3,"min1sd":11.2,"median":12.2,"1sd":13.4,"2sd":14.7,"3sd":16.2},{"tinggi":89.5,"min3sd":9.6,"min2sd":10.4,"min1sd":11.3,"median":12.3,"1sd":13.5,"2sd":14.8,"3sd":16.4},{"tinggi":90,"min3sd":9.7,"min2sd":10.5,"min1sd":11.4,"median":12.5,"1sd":13.7,"2sd":15,"3sd":16.5},{"tinggi":90.5,"min3sd":9.8,"min2sd":10.6,"min1sd":11.5,"median":12.6,"1sd":13.8,"2sd":15.1,"3sd":16.7},{"tinggi":91,"min3sd":9.9,"min2sd":10.7,"min1sd":11.7,"median":12.7,"1sd":13.9,"2sd":15.3,"3sd":16.9},{"tinggi":91.5,"min3sd":10,"min2sd":10.8,"min1sd":11.8,"median":12.8,"1sd":14.1,"2sd":15.5,"3sd":17},{"tinggi":92,"min3sd":10.1,"min2sd":10.9,"min1sd":11.9,"median":13,"1sd":14.2,"2sd":15.6,"3sd":17.2},{"tinggi":92.5,"min3sd":10.1,"min2sd":11,"min1sd":12,"median":13.1,"1sd":14.3,"2sd":15.8,"3sd":17.4},{"tinggi":93,"min3sd":10.2,"min2sd":11.1,"min1sd":12.1,"median":13.2,"1sd":14.5,"2sd":15.9,"3sd":17.5},{"tinggi":93.5,"min3sd":10.3,"min2sd":11.2,"min1sd":12.2,"median":13.3,"1sd":14.6,"2sd":16.1,"3sd":17.7},{"tinggi":94,"min3sd":10.4,"min2sd":11.3,"min1sd":12.3,"median":13.5,"1sd":14.7,"2sd":16.2,"3sd":17.9},{"tinggi":94.5,"min3sd":10.5,"min2sd":11.4,"min1sd":12.4,"median":13.6,"1sd":14.9,"2sd":16.4,"3sd":18},{"tinggi":95,"min3sd":10.6,"min2sd":11.5,"min1sd":12.6,"median":13.7,"1sd":15,"2sd":16.5,"3sd":18.2},{"tinggi":95.5,"min3sd":10.7,"min2sd":11.6,"min1sd":12.7,"median":13.8,"1sd":15.2,"2sd":16.7,"3sd":18.4},{"tinggi":96,"min3sd":10.8,"min2sd":11.7,"min1sd":12.8,"median":14,"1sd":15.3,"2sd":16.8,"3sd":18.6},{"tinggi":96.5,"min3sd":10.9,"min2sd":11.8,"min1sd":12.9,"median":14.1,"1sd":15.4,"2sd":17,"3sd":18.7},{"tinggi":97,"min3sd":11,"min2sd":12,"min1sd":13,"median":14.2,"1sd":15.6,"2sd":17.1,"3sd":18.9},{"tinggi":97.5,"min3sd":11.1,"min2sd":12.1,"min1sd":13.1,"median":14.4,"1sd":15.7,"2sd":17.3,"3sd":19.1},{"tinggi":98,"min3sd":11.2,"min2sd":12.2,"min1sd":13.3,"median":14.5,"1sd":15.9,"2sd":17.5,"3sd":19.3},{"tinggi":98.5,"min3sd":11.3,"min2sd":12.3,"min1sd":13.4,"median":14.6,"1sd":16,"2sd":17.6,"3sd":19.5},{"tinggi":99,"min3sd":11.4,"min2sd":12.4,"min1sd":13.5,"median":14.8,"1sd":16.2,"2sd":17.8,"3sd":19.6},{"tinggi":99.5,"min3sd":11.5,"min2sd":12.5,"min1sd":13.6,"median":14.9,"1sd":16.3,"2sd":18,"3sd":19.8},{"tinggi":100,"min3sd":11.6,"min2sd":12.6,"min1sd":13.7,"median":15,"1sd":16.5,"2sd":18.1,"3sd":20},{"tinggi":100.5,"min3sd":11.7,"min2sd":12.7,"min1sd":13.9,"median":15.2,"1sd":16.6,"2sd":18.3,"3sd":20.2},{"tinggi":101,"min3sd":11.8,"min2sd":12.8,"min1sd":14,"median":15.3,"1sd":16.8,"2sd":18.5,"3sd":20.4},{"tinggi":101.5,"min3sd":11.9,"min2sd":13,"min1sd":14.1,"median":15.5,"1sd":17,"2sd":18.7,"3sd":20.6},{"tinggi":102,"min3sd":12,"min2sd":13.1,"min1sd":14.3,"median":15.6,"1sd":17.1,"2sd":18.9,"3sd":20.8},{"tinggi":102.5,"min3sd":12.1,"min2sd":13.2,"min1sd":14.4,"median":15.8,"1sd":17.3,"2sd":19,"3sd":21},{"tinggi":103,"min3sd":12.3,"min2sd":13.3,"min1sd":14.5,"median":15.9,"1sd":17.5,"2sd":19.2,"3sd":21.3},{"tinggi":103.5,"min3sd":12.4,"min2sd":13.5,"min1sd":14.7,"median":16.1,"1sd":17.6,"2sd":19.4,"3sd":21.5},{"tinggi":104,"min3sd":12.5,"min2sd":13.6,"min1sd":14.8,"median":16.2,"1sd":17.8,"2sd":19.6,"3sd":21.7},{"tinggi":104.5,"min3sd":12.6,"min2sd":13.7,"min1sd":15,"median":16.4,"1sd":18,"2sd":19.8,"3sd":21.9},{"tinggi":105,"min3sd":12.7,"min2sd":13.8,"min1sd":15.1,"median":16.5,"1sd":18.2,"2sd":20,"3sd":22.2},{"tinggi":105.5,"min3sd":12.8,"min2sd":14,"min1sd":15.3,"median":16.7,"1sd":18.4,"2sd":20.2,"3sd":22.4},{"tinggi":106,"min3sd":13,"min2sd":14.1,"min1sd":15.4,"median":16.9,"1sd":18.5,"2sd":20.5,"3sd":22.6},{"tinggi":106.5,"min3sd":13.1,"min2sd":14.3,"min1sd":15.6,"median":17.1,"1sd":18.7,"2sd":20.7,"3sd":22.9},{"tinggi":107,"min3sd":13.2,"min2sd":14.4,"min1sd":15.7,"median":17.2,"1sd":18.9,"2sd":20.9,"3sd":23.1},{"tinggi":107.5,"min3sd":13.3,"min2sd":14.5,"min1sd":15.9,"median":17.4,"1sd":19.1,"2sd":21.1,"3sd":23.4},{"tinggi":108,"min3sd":13.5,"min2sd":14.7,"min1sd":16,"median":17.6,"1sd":19.3,"2sd":21.3,"3sd":23.6},{"tinggi":108.5,"min3sd":13.6,"min2sd":14.8,"min1sd":16.2,"median":17.8,"1sd":19.5,"2sd":21.6,"3sd":23.9},{"tinggi":109,"min3sd":13.7,"min2sd":15,"min1sd":16.4,"median":18,"1sd":19.7,"2sd":21.8,"3sd":24.2},{"tinggi":109.5,"min3sd":13.9,"min2sd":15.1,"min1sd":16.5,"median":18.1,"1sd":20,"2sd":22,"3sd":24.4},{"tinggi":110,"min3sd":14,"min2sd":15.3,"min1sd":16.7,"median":18.3,"1sd":20.2,"2sd":22.3,"3sd":24.7}]', true);

        return $data;
    }

    public static function table_bb_tb_2460_perempuan()
    {
        $data=json_decode('[{"tinggi":65,"min3sd":5.6,"min2sd":6.1,"min1sd":6.6,"median":7.2,"1sd":7.9,"2sd":8.7,"3sd":9.7},{"tinggi":65.5,"min3sd":5.7,"min2sd":6.2,"min1sd":6.7,"median":7.4,"1sd":8.1,"2sd":8.9,"3sd":9.8},{"tinggi":66,"min3sd":5.8,"min2sd":6.3,"min1sd":6.8,"median":7.5,"1sd":8.2,"2sd":9,"3sd":10},{"tinggi":66.5,"min3sd":5.8,"min2sd":6.4,"min1sd":6.9,"median":7.6,"1sd":8.3,"2sd":9.1,"3sd":10.1},{"tinggi":67,"min3sd":5.9,"min2sd":6.4,"min1sd":7,"median":7.7,"1sd":8.4,"2sd":9.3,"3sd":10.2},{"tinggi":67.5,"min3sd":6,"min2sd":6.5,"min1sd":7.1,"median":7.8,"1sd":8.5,"2sd":9.4,"3sd":10.4},{"tinggi":68,"min3sd":6.1,"min2sd":6.6,"min1sd":7.2,"median":7.9,"1sd":8.7,"2sd":9.5,"3sd":10.5},{"tinggi":68.5,"min3sd":6.2,"min2sd":6.7,"min1sd":7.3,"median":8,"1sd":8.8,"2sd":9.7,"3sd":10.7},{"tinggi":69,"min3sd":6.3,"min2sd":6.8,"min1sd":7.4,"median":8.1,"1sd":8.9,"2sd":9.8,"3sd":10.8},{"tinggi":69.5,"min3sd":6,"min2sd":6.9,"min1sd":7.5,"median":8.2,"1sd":9,"2sd":9.9,"3sd":10.9},{"tinggi":70,"min3sd":6.4,"min2sd":7,"min1sd":7.6,"median":8.3,"1sd":9.1,"2sd":10,"3sd":11.1},{"tinggi":70.5,"min3sd":6.5,"min2sd":7.1,"min1sd":7.7,"median":8.4,"1sd":9.2,"2sd":10.1,"3sd":11.2},{"tinggi":71,"min3sd":6.6,"min2sd":7.1,"min1sd":7.8,"median":8.5,"1sd":9.3,"2sd":10.3,"3sd":11.3},{"tinggi":71.5,"min3sd":6.7,"min2sd":7.2,"min1sd":7.9,"median":8.6,"1sd":9.4,"2sd":10.4,"3sd":11.5},{"tinggi":72,"min3sd":6.7,"min2sd":7.3,"min1sd":8,"median":8.7,"1sd":9.5,"2sd":10.5,"3sd":11.6},{"tinggi":72.5,"min3sd":6.8,"min2sd":7.4,"min1sd":8.1,"median":8.8,"1sd":9.7,"2sd":10.6,"3sd":11.7},{"tinggi":73,"min3sd":6.9,"min2sd":7.5,"min1sd":8.1,"median":8.9,"1sd":9.8,"2sd":10.7,"3sd":11.8},{"tinggi":73.5,"min3sd":7,"min2sd":7.6,"min1sd":8.2,"median":9,"1sd":9.9,"2sd":10.8,"3sd":12},{"tinggi":74,"min3sd":7,"min2sd":7.6,"min1sd":8.3,"median":9.1,"1sd":10,"2sd":11,"3sd":12.1},{"tinggi":74.5,"min3sd":7.1,"min2sd":7.7,"min1sd":8.4,"median":9.2,"1sd":10.1,"2sd":11.1,"3sd":12.2},{"tinggi":75,"min3sd":7.2,"min2sd":7.8,"min1sd":8.5,"median":9.3,"1sd":10.2,"2sd":11.2,"3sd":12.3},{"tinggi":75.5,"min3sd":7.2,"min2sd":7.9,"min1sd":8.6,"median":9.4,"1sd":10.3,"2sd":11.3,"3sd":12.5},{"tinggi":76,"min3sd":7.3,"min2sd":8,"min1sd":8.7,"median":9.5,"1sd":10.4,"2sd":11.4,"3sd":12.6},{"tinggi":76.5,"min3sd":7.4,"min2sd":8,"min1sd":8.7,"median":9.6,"1sd":10.5,"2sd":11.5,"3sd":12.7},{"tinggi":77,"min3sd":7.5,"min2sd":8.1,"min1sd":8.8,"median":9.6,"1sd":10.6,"2sd":11.6,"3sd":12.8},{"tinggi":77.5,"min3sd":7.5,"min2sd":8.2,"min1sd":8.9,"median":9.7,"1sd":10.7,"2sd":11.7,"3sd":12.9},{"tinggi":78,"min3sd":7.6,"min2sd":8.3,"min1sd":9,"median":9.8,"1sd":10.8,"2sd":11.8,"3sd":13.1},{"tinggi":78.5,"min3sd":7.7,"min2sd":8.4,"min1sd":9.1,"median":9.9,"1sd":10.9,"2sd":12,"3sd":13.2},{"tinggi":79,"min3sd":7.8,"min2sd":8.4,"min1sd":9.2,"median":10,"1sd":11,"2sd":12.1,"3sd":13.3},{"tinggi":79.5,"min3sd":7.8,"min2sd":8.5,"min1sd":9.3,"median":10.1,"1sd":11.1,"2sd":12.2,"3sd":13.4},{"tinggi":80,"min3sd":7.9,"min2sd":8.6,"min1sd":9.4,"median":10.2,"1sd":11.2,"2sd":12.3,"3sd":13.6},{"tinggi":80.5,"min3sd":8,"min2sd":8.7,"min1sd":9.5,"median":10.3,"1sd":11.3,"2sd":12.4,"3sd":13.7},{"tinggi":81,"min3sd":8.1,"min2sd":8.8,"min1sd":9.6,"median":10.4,"1sd":11.4,"2sd":12.6,"3sd":13.9},{"tinggi":81.5,"min3sd":8.2,"min2sd":8.9,"min1sd":9.7,"median":10.6,"1sd":11.6,"2sd":12.7,"3sd":14},{"tinggi":82,"min3sd":8.3,"min2sd":9,"min1sd":9.8,"median":10.7,"1sd":11.7,"2sd":12.8,"3sd":14.1},{"tinggi":82.5,"min3sd":8.4,"min2sd":9.1,"min1sd":9.9,"median":10.8,"1sd":11.8,"2sd":13,"3sd":14.3},{"tinggi":83,"min3sd":8.5,"min2sd":9.2,"min1sd":10,"median":10.9,"1sd":11.9,"2sd":13.1,"3sd":14.5},{"tinggi":83.5,"min3sd":8.5,"min2sd":9.3,"min1sd":10.1,"median":11,"1sd":12.1,"2sd":13.3,"3sd":14.6},{"tinggi":84,"min3sd":8.6,"min2sd":9.4,"min1sd":10.2,"median":11.1,"1sd":12.2,"2sd":13.4,"3sd":14.8},{"tinggi":84.5,"min3sd":8.7,"min2sd":9.5,"min1sd":10.3,"median":11.3,"1sd":12.3,"2sd":13.5,"3sd":14.9},{"tinggi":85,"min3sd":8.8,"min2sd":9.6,"min1sd":10.4,"median":11.4,"1sd":12.5,"2sd":13.7,"3sd":15.1},{"tinggi":85.5,"min3sd":8.9,"min2sd":9.7,"min1sd":10.6,"median":11.5,"1sd":12.6,"2sd":13.8,"3sd":15.3},{"tinggi":86,"min3sd":9,"min2sd":9.8,"min1sd":10.7,"median":11.6,"1sd":12.7,"2sd":14,"3sd":15.4},{"tinggi":86.5,"min3sd":9.1,"min2sd":9.9,"min1sd":10.8,"median":11.8,"1sd":12.9,"2sd":14.2,"3sd":15.6},{"tinggi":87,"min3sd":9.2,"min2sd":10,"min1sd":10.9,"median":11.9,"1sd":13,"2sd":14.3,"3sd":15.8},{"tinggi":87.5,"min3sd":9.3,"min2sd":10.1,"min1sd":11,"median":12,"1sd":13.2,"2sd":14.5,"3sd":15.9},{"tinggi":88,"min3sd":9.4,"min2sd":10.2,"min1sd":11.1,"median":12.1,"1sd":13.3,"2sd":14.6,"3sd":16.1},{"tinggi":88.5,"min3sd":9.5,"min2sd":10.3,"min1sd":11.2,"median":12.3,"1sd":13.4,"2sd":14.8,"3sd":16.3},{"tinggi":89,"min3sd":9.6,"min2sd":10.4,"min1sd":11.4,"median":12.4,"1sd":13.6,"2sd":14.9,"3sd":16.4},{"tinggi":89.5,"min3sd":9.7,"min2sd":10.5,"min1sd":11.5,"median":12.5,"1sd":13.7,"2sd":15.1,"3sd":16.6},{"tinggi":90,"min3sd":9.8,"min2sd":10.6,"min1sd":11.6,"median":12.6,"1sd":13.8,"2sd":15.2,"3sd":16.8},{"tinggi":90.5,"min3sd":9.9,"min2sd":10.7,"min1sd":11.7,"median":12.8,"1sd":14,"2sd":15.4,"3sd":16.9},{"tinggi":91,"min3sd":10,"min2sd":10.9,"min1sd":11.8,"median":12.9,"1sd":14.1,"2sd":15.5,"3sd":17.1},{"tinggi":91.5,"min3sd":10.1,"min2sd":11,"min1sd":11.9,"median":13,"1sd":14.3,"2sd":15.7,"3sd":17.3},{"tinggi":92,"min3sd":10.2,"min2sd":11.1,"min1sd":12,"median":13.1,"1sd":14.4,"2sd":15.8,"3sd":17.4},{"tinggi":92.5,"min3sd":10.3,"min2sd":11.2,"min1sd":12.1,"median":13.3,"1sd":14.5,"2sd":16,"3sd":17.6},{"tinggi":93,"min3sd":10.4,"min2sd":11.3,"min1sd":12.3,"median":13.4,"1sd":14.7,"2sd":16.1,"3sd":17.8},{"tinggi":93.5,"min3sd":10.5,"min2sd":11.4,"min1sd":12.4,"median":13.5,"1sd":14.8,"2sd":16.3,"3sd":17.9},{"tinggi":94,"min3sd":10.6,"min2sd":11.5,"min1sd":12.5,"median":13.6,"1sd":14.9,"2sd":16.4,"3sd":18.1},{"tinggi":94.5,"min3sd":10.7,"min2sd":11.6,"min1sd":12.6,"median":13.8,"1sd":15.1,"2sd":16.6,"3sd":18.3},{"tinggi":95,"min3sd":10.8,"min2sd":11.7,"min1sd":12.7,"median":13.9,"1sd":15.2,"2sd":16.7,"3sd":18.5},{"tinggi":95.5,"min3sd":10.8,"min2sd":11.8,"min1sd":12.8,"median":14,"1sd":15.4,"2sd":16.9,"3sd":18.6},{"tinggi":96,"min3sd":10.9,"min2sd":11.9,"min1sd":12.9,"median":14.1,"1sd":15.5,"2sd":17,"3sd":18.8},{"tinggi":96.5,"min3sd":11,"min2sd":12,"min1sd":13.1,"median":14.3,"1sd":15.6,"2sd":17.2,"3sd":19},{"tinggi":97,"min3sd":11.1,"min2sd":12.1,"min1sd":13.2,"median":14.4,"1sd":15.8,"2sd":17.4,"3sd":19.2},{"tinggi":97.5,"min3sd":11.2,"min2sd":12.2,"min1sd":13.3,"median":14.5,"1sd":15.9,"2sd":17.5,"3sd":19.3},{"tinggi":98,"min3sd":11.3,"min2sd":12.3,"min1sd":13.4,"median":14.7,"1sd":16.1,"2sd":17.7,"3sd":19.5},{"tinggi":98.5,"min3sd":11.4,"min2sd":12.4,"min1sd":13.5,"median":14.8,"1sd":16.2,"2sd":17.9,"3sd":19.7},{"tinggi":99,"min3sd":11.5,"min2sd":12.5,"min1sd":13.7,"median":14.9,"1sd":16.4,"2sd":18,"3sd":19.9},{"tinggi":99.5,"min3sd":11.6,"min2sd":12.7,"min1sd":13.8,"median":15.1,"1sd":16.5,"2sd":18.2,"3sd":20.1},{"tinggi":100,"min3sd":11.7,"min2sd":12.8,"min1sd":13.9,"median":15.2,"1sd":16.7,"2sd":18.4,"3sd":20.3},{"tinggi":100.5,"min3sd":11.9,"min2sd":12.9,"min1sd":14.1,"median":15.4,"1sd":16.9,"2sd":18.6,"3sd":20.5},{"tinggi":101,"min3sd":12,"min2sd":13,"min1sd":14.2,"median":15.5,"1sd":17,"2sd":18.7,"3sd":20.7},{"tinggi":101.5,"min3sd":12.1,"min2sd":13.1,"min1sd":14.3,"median":15.7,"1sd":17.2,"2sd":18.9,"3sd":20.9},{"tinggi":102,"min3sd":12.2,"min2sd":13.3,"min1sd":14.5,"median":15.8,"1sd":17.4,"2sd":19.1,"3sd":21.1},{"tinggi":102.5,"min3sd":12.3,"min2sd":13.4,"min1sd":14.6,"median":16,"1sd":17.5,"2sd":19.3,"3sd":21.4},{"tinggi":103,"min3sd":12.4,"min2sd":13.5,"min1sd":14.7,"median":16.1,"1sd":17.7,"2sd":19.5,"3sd":21.6},{"tinggi":103.5,"min3sd":12.5,"min2sd":13.6,"min1sd":14.9,"median":16.3,"1sd":17.9,"2sd":19.7,"3sd":21.8},{"tinggi":104,"min3sd":12.6,"min2sd":13.8,"min1sd":15,"median":16.4,"1sd":18.1,"2sd":19.9,"3sd":22},{"tinggi":104.5,"min3sd":12.8,"min2sd":13.9,"min1sd":15.2,"median":16.6,"1sd":18.2,"2sd":20.1,"3sd":22.3},{"tinggi":105,"min3sd":12.9,"min2sd":14,"min1sd":15.3,"median":16.8,"1sd":18.4,"2sd":20.3,"3sd":22.5},{"tinggi":105.5,"min3sd":13,"min2sd":14.2,"min1sd":15.5,"median":16.9,"1sd":18.6,"2sd":20.5,"3sd":22.7},{"tinggi":106,"min3sd":13.1,"min2sd":14.3,"min1sd":15.6,"median":17.1,"1sd":18.8,"2sd":20.8,"3sd":23},{"tinggi":106.5,"min3sd":13.3,"min2sd":14.5,"min1sd":15.8,"median":17.3,"1sd":19,"2sd":21,"3sd":23.2},{"tinggi":107,"min3sd":13.4,"min2sd":14.6,"min1sd":15.9,"median":17.5,"1sd":19.2,"2sd":21.2,"3sd":23.5},{"tinggi":107.5,"min3sd":13.5,"min2sd":14.7,"min1sd":16.1,"median":17.7,"1sd":19.4,"2sd":21.4,"3sd":23.7},{"tinggi":108,"min3sd":13.7,"min2sd":14.9,"min1sd":16.3,"median":17.8,"1sd":19.6,"2sd":21.7,"3sd":24},{"tinggi":108.5,"min3sd":13.8,"min2sd":15,"min1sd":16.4,"median":18,"1sd":19.8,"2sd":21.9,"3sd":24.3},{"tinggi":109,"min3sd":13.9,"min2sd":15.2,"min1sd":16.6,"median":18.2,"1sd":20,"2sd":22.1,"3sd":24.5},{"tinggi":109.5,"min3sd":14.1,"min2sd":15.4,"min1sd":16.8,"median":18.4,"1sd":20.3,"2sd":22.4,"3sd":24.8},{"tinggi":110,"min3sd":14.2,"min2sd":15.5,"min1sd":17,"median":18.6,"1sd":20.5,"2sd":22.6,"3sd":25.1},{"tinggi":110.5,"min3sd":14.4,"min2sd":15.7,"min1sd":17.1,"median":18.8,"1sd":20.7,"2sd":22.9,"3sd":25.4},{"tinggi":111,"min3sd":14.5,"min2sd":15.8,"min1sd":17.3,"median":19,"1sd":20.9,"2sd":23.1,"3sd":25.7},{"tinggi":111.5,"min3sd":14.7,"min2sd":16,"min1sd":17.5,"median":19.2,"1sd":21.2,"2sd":23.4,"3sd":26},{"tinggi":112,"min3sd":14.8,"min2sd":16.2,"min1sd":17.7,"median":19.4,"1sd":21.4,"2sd":23.6,"3sd":26.2},{"tinggi":112.5,"min3sd":15,"min2sd":16.3,"min1sd":17.9,"median":19.6,"1sd":21.6,"2sd":23.9,"3sd":26.5},{"tinggi":113,"min3sd":15.1,"min2sd":16.5,"min1sd":18,"median":19.8,"1sd":21.8,"2sd":24.2,"3sd":26.8},{"tinggi":113.5,"min3sd":15.3,"min2sd":16.7,"min1sd":18.2,"median":20,"1sd":22.1,"2sd":24.4,"3sd":27.1},{"tinggi":114,"min3sd":15.4,"min2sd":16.8,"min1sd":18.4,"median":20.2,"1sd":22.3,"2sd":24.7,"3sd":27.4},{"tinggi":114.5,"min3sd":15.6,"min2sd":17,"min1sd":18.6,"median":20.5,"1sd":22.6,"2sd":25,"3sd":27.8},{"tinggi":115,"min3sd":15.7,"min2sd":17.2,"min1sd":18.8,"median":20.7,"1sd":22.8,"2sd":25.2,"3sd":28.1},{"tinggi":115.5,"min3sd":15.9,"min2sd":17.3,"min1sd":19,"median":20.9,"1sd":23,"2sd":25.5,"3sd":28.4},{"tinggi":116,"min3sd":16,"min2sd":17.5,"min1sd":19.2,"median":21.1,"1sd":23.3,"2sd":25.8,"3sd":28.7},{"tinggi":116.5,"min3sd":16.2,"min2sd":17.7,"min1sd":19.4,"median":21.3,"1sd":23.5,"2sd":26.1,"3sd":29},{"tinggi":117,"min3sd":16.3,"min2sd":17.8,"min1sd":19.6,"median":21.5,"1sd":23.8,"2sd":26.3,"3sd":29.3},{"tinggi":117.5,"min3sd":16.5,"min2sd":18,"min1sd":19.8,"median":21.7,"1sd":24,"2sd":26.6,"3sd":29.6},{"tinggi":118,"min3sd":16.6,"min2sd":18.2,"min1sd":19.9,"median":22,"1sd":24.2,"2sd":26.9,"3sd":29.9},{"tinggi":118.5,"min3sd":16.8,"min2sd":18.4,"min1sd":20.1,"median":22.2,"1sd":24.5,"2sd":27.2,"3sd":30.3},{"tinggi":119,"min3sd":16.9,"min2sd":18.5,"min1sd":20.3,"median":22.4,"1sd":24.7,"2sd":27.4,"3sd":30.6},{"tinggi":119.5,"min3sd":17.1,"min2sd":18.7,"min1sd":20.5,"median":22.6,"1sd":25,"2sd":27.7,"3sd":30.9},{"tinggi":120,"min3sd":17.3,"min2sd":18.9,"min1sd":20.7,"median":22.8,"1sd":25.2,"2sd":28,"3sd":31.2}]', true);

        return $data;
    }

    public static function status_gizi_laki_laki($sbb, $umur)
    {
        $umur=(int)$umur;

        if($umur==0 || $umur>60 || trim($sbb)==""){
            return "O";
        }
        if(in_array($umur, [1, 3])){
            if($sbb>=800) return "N";
            else return "T";
        }
        if($umur==2){
            if($sbb>=900) return "N";
            else return "T";
        }
        if($umur==4){
            if($sbb>=600) return "N";
            else return "T";
        }
        if($umur==5){
            if($sbb>=500) return "N";
            else return "T";
        }
        if(in_array($umur, [6, 7])){
            if($sbb>=400) return "N";
            else return "T";
        }
        if(in_array($umur, [8, 9, 10, 11])){
            if($sbb>=300) return "N";
            else return "T";
        }
        if($umur>=12 && $umur<=60){
            if($sbb>=200) return "N";
            else return "T";
        }
    }
    
    public static function status_gizi_perempuan($sbb, $umur)
    {
        $umur=(int)$umur;

        if($umur==0 || $umur>60 || trim($sbb)==""){
            return "O";
        }
        if(in_array($umur, [1, 3])){
            if($sbb>=800) return "N";
            else return "T";
        }
        if($umur==2){
            if($sbb>=900) return "N";
            else return "T";
        }
        if($umur==4){
            if($sbb>=600) return "N";
            else return "T";
        }
        if($umur==5){
            if($sbb>=500) return "N";
            else return "T";
        }
        if($umur==6){
            if($sbb>=400) return "N";
            else return "T";
        }
        if(in_array($umur, [7, 8, 9, 10])){
            if($sbb>=300) return "N";
            else return "T";
        }
        if($umur>=11 && $umur<=60){
            if($sbb>=200) return "N";
            else return "T";
        }
    }

    public static function generate_status_gizi($data){
        //prev month antropometri
        $r_bulan=SkriningBalitaModel::
            where("data_anak->nik", $data['nik'])
            ->where("usia_saat_ukur", $data['umur']-1)
            ->orderBy("id_skrining_balita")
            ->lockForUpdate()
            ->first();
        
        $ssb="";
        if(!is_null($r_bulan)){
            $ssb=($data['berat_badan']*1000)-($r_bulan['berat_badan']*1000);
        }

        //exec
        if($data['jenis_kelamin']=="L"){
            return SkriningBalitaRepo::status_gizi_laki_laki($ssb, $data['umur']);
        }
        else{
            return SkriningBalitaRepo::status_gizi_perempuan($ssb, $data['umur']);
        }
    }

    public static function generate_antropometri_berat_badan_umur($data)
    {
        if($data['jenis_kelamin']=="L"){
            $table=SkriningBalitaRepo::table_bb_u_laki_laki();
        }
        else{
            $table=SkriningBalitaRepo::table_bb_u_perempuan();
        }
    
        $search=array_find_by_key($table, "umur", $data['umur']);
        if(isset($search['umur'])){
            $result=[];
            
            if(trim(strval($data['berat_badan']))==""){
                $result=[
                    'success'   =>false,
                    'kategori'  =>"",
                    'text'      =>""
                ];
            }
            elseif($data['berat_badan']<$search['min3sd']){
                $result=[
                    'success'   =>true,
                    'kategori'  =>"gizi_buruk",
                    'text'      =>"Berat Badan sangat Kurang(severely underweight)"
                ];
            }
            elseif($data['berat_badan']>=$search['min3sd']&&$data['berat_badan']<$search['min2sd']){
                $result=[
                    'success'   =>true,
                    'kategori'  =>"gizi_kurang",
                    'text'      =>"Berat badan kurang (underweight)"
                ];
            }
            elseif($data['berat_badan']>=$search['min2sd']&&$data['berat_badan']<=$search['1sd']){
                $result=[
                    'success'   =>true,
                    'kategori'  =>"gizi_baik",
                    'text'      =>"Berat badan normal"
                ];
            }
            elseif($data['berat_badan']>$search['1sd']){
                $result=[
                    'success'   =>true,
                    'kategori'  =>"gizi_lebih",
                    'text'      =>"Risiko Berat badan lebih"
                ];
            }
    
            $data=array_merge($data, [
                'result'    =>$result
            ]);
        }
        else{
            $data=array_merge($data, [
                'result'    =>[
                    'kategori'  =>"unknown",
                    'success'   =>false
                ]
            ]);
        }

        return $data;
    }
    
    //PENGUKURAN UMUR 24 DISET TELENTANG
    public static function generate_antropometri_panjang_badan_umur($data)
    {
        if($data['jenis_kelamin']=="L"){
            $table=SkriningBalitaRepo::table_pb_u_laki_laki();
        }
        else{
            $table=SkriningBalitaRepo::table_pb_u_perempuan();
        }
    
        $search=array_find_by_key($table, "umur", $data['umur']);
        if($data['umur']=="24"){
            $search=[];
            foreach($table as $val){
                if($val["umur"]=="24"&&$val['metode']=="telentang"){
                    $search=$val;
                    break;
                }
            }
        }
        if(isset($search['umur'])){
            $result=[];
            
            if(trim(strval($data['tinggi_badan']))==""){
                $result=[
                    'success'   =>false,
                    'kategori'  =>"",
                    'text'      =>""
                ];
            }
            elseif($data['tinggi_badan']<$search['min3sd']){
                $result=[
                    'success'   =>true,
                    'kategori'  =>"sangat_pendek",
                    'text'      =>"Sangat pendek (severely stunted)"
                ];
            }
            elseif($data['tinggi_badan']>=$search['min3sd']&&$data['tinggi_badan']<$search['min2sd']){
                $result=[
                    'success'   =>true,
                    'kategori'  =>"pendek",
                    'text'      =>"Pendek (stunted)"
                ];
            }
            elseif($data['tinggi_badan']>=$search['min2sd']&&$data['tinggi_badan']<=$search['3sd']){
                $result=[
                    'success'   =>true,
                    'kategori'  =>"normal",
                    'text'      =>"Normal"
                ];
            }
            elseif($data['tinggi_badan']>$search['3sd']){
                $result=[
                    'success'   =>true,
                    'kategori'  =>"tinggi",
                    'text'      =>"Tinggi"
                ];
            }
    
            $data=array_merge($data, [
                'result'    =>$result
            ]);
        }
        else{
            $data=array_merge($data, [
                'result'    =>[
                    'kategori'  =>"unknown",
                    'success'   =>false
                ]
            ]);
        }

        return $data;
    }

    public static function generate_antropometri_berat_badan_tinggi_badan($data)
    {
        if($data['jenis_kelamin']=="L"){
            if($data['umur']<=24 && $data['umur']>=0){
                $table=SkriningBalitaRepo::table_bb_tb_024_laki_laki();
            }
            elseif($data['umur']<=60 && $data['umur']>24){
                $table=SkriningBalitaRepo::table_bb_tb_2460_laki_laki();
            }
            else{
                $table=[];
            }
        }
        else{
            if($data['umur']<=24 && $data['umur']>=0){
                $table=SkriningBalitaRepo::table_bb_tb_024_perempuan();
            }
            elseif($data['umur']<=60 && $data['umur']>24){
                $table=SkriningBalitaRepo::table_bb_tb_2460_perempuan();
            }
            else{
                $table=[];
            }
        }
        
        //tinggi badan(round up middle)
        $data['tinggi_badan']=roundup_middle($data['tinggi_badan']);

        if(trim(strval($data['berat_badan']))=="" || trim(strval($data['tinggi_badan']))==""){
            return array_merge($data, [
                'result'    =>[
                    'kategori'  =>"",
                    'success'   =>false
                ]
            ]);
        }

        //hitung
        $search=array_find_by_key($table, "tinggi", $data['tinggi_badan']);
        if(isset($search['tinggi'])){
            $result=[];
    
            if($data['berat_badan']<$search['min3sd']){
                $result=[
                    'success'   =>true,
                    'kategori'  =>"gizi_buruk",
                    'text'      =>"Berat Badan sangat Kurang(severely underweight)"
                ];
            }
            elseif($data['berat_badan']>=$search['min3sd']&&$data['berat_badan']<$search['min2sd']){
                $result=[
                    'success'   =>true,
                    'kategori'  =>"gizi_kurang",
                    'text'      =>"Berat badan kurang (underweight)"
                ];
            }
            elseif($data['berat_badan']>=$search['min2sd']&&$data['berat_badan']<=$search['1sd']){
                $result=[
                    'success'   =>true,
                    'kategori'  =>"gizi_baik",
                    'text'      =>"Berat badan normal"
                ];
            }
            elseif($data['berat_badan']>$search['1sd']&&$data['berat_badan']<=$search['2sd']){
                $result=[
                    'success'   =>true,
                    'kategori'  =>"beresiko_gizi_lebih",
                    'text'      =>"Risiko Berat badan lebih"
                ];
            }
            elseif($data['berat_badan']>$search['2sd']&&$data['berat_badan']<=$search['3sd']){
                $result=[
                    'success'   =>true,
                    'kategori'  =>"gizi_lebih",
                    'text'      =>"Risiko Berat badan lebih"
                ];
            }
            elseif($data['berat_badan']>$search['3sd']){
                $result=[
                    'success'   =>true,
                    'kategori'  =>"obesitas",
                    'text'      =>"Risiko Berat badan lebih"
                ];
            }
    
            $data=array_merge($data, [
                'result'    =>$result
            ]);
        }
        else{
            $data=array_merge($data, [
                'result'    =>[
                    'kategori'  =>"unknown",
                    'success'   =>false
                ]
            ]);
        }

        return $data;
    }
}