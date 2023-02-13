<?php

namespace App\Repository;


use App\Models\SkriningBalitaModel;
use App\Models\RegionModel;


class StuntingRepo{
    
    public static function gets_stunting_by_region_desa($params)
    {
        //params
        $params['district_id']=trim($params['district_id']);

        //query
        //--count stunting
        $query=RegionModel::withCount(['posyandu as count_stunting'=>function($q){
            return $q->join(
                \DB::raw("(select 
                    max(usia_saat_ukur) as usia_saat_ukur, 
                    SUBSTRING(max(CONCAT(LPAD(usia_saat_ukur, 11, '0'), COALESCE(`id_skrining_balita`, ''))), 12) as id_skrining_balita, 
                    SUBSTRING(max(CONCAT(LPAD(usia_saat_ukur, 11, '0'), COALESCE(`id_user`, ''))), 12) as id_user, 
                    SUBSTRING(max(CONCAT(LPAD(usia_saat_ukur, 11, '0'), data_anak)), 12) as data_anak, 
                    SUBSTRING(max(CONCAT(LPAD(usia_saat_ukur, 11, '0'), hasil_tinggi_badan_per_umur)), 12) as hasil_tinggi_badan_per_umur 
                from tbl_skrining_balita 
                    group by json_unquote(json_extract(`data_anak`, '$.nik')) 
                        having hasil_tinggi_badan_per_umur in ('pendek', 'sangat_pendek')) gen2_tbl_skrining_balita"),
                "gen2_tbl_skrining_balita.id_user",
                "=",
                "tbl_users.id_user"
            );
        }]);
        //--district
        if($params['district_id']!=""){
            $query=$query->where("nested", $params['district_id']);
        }
        //--type, search, order
        $query=$query->where("type", "desa")
            ->where("region", "like", "%".$params['q']."%")
            ->orderBy("region");

        //return
        return $query->get()->toArray();
    }

    public static function gets_stunting_by_region_kecamatan($params)
    {
        //params
        $params['district_id']=trim($params['district_id']);

        //query
        //--count stunting
        $query=RegionModel::withCount(["posyandu_kecamatan as count_stunting"=>function($q){
            return $q->join(
                \DB::raw("(select 
                    max(usia_saat_ukur) as usia_saat_ukur, 
                    SUBSTRING(max(CONCAT(LPAD(usia_saat_ukur, 11, '0'), COALESCE(`id_skrining_balita`, ''))), 12) as id_skrining_balita, 
                    SUBSTRING(max(CONCAT(LPAD(usia_saat_ukur, 11, '0'), COALESCE(`id_user`, ''))), 12) as id_user, 
                    SUBSTRING(max(CONCAT(LPAD(usia_saat_ukur, 11, '0'), data_anak)), 12) as data_anak, 
                    SUBSTRING(max(CONCAT(LPAD(usia_saat_ukur, 11, '0'), hasil_tinggi_badan_per_umur)), 12) as hasil_tinggi_badan_per_umur 
                from tbl_skrining_balita 
                    group by json_unquote(json_extract(`data_anak`, '$.nik')) 
                        having hasil_tinggi_badan_per_umur in ('pendek', 'sangat_pendek')) gen2_tbl_skrining_balita"),
                "gen2_tbl_skrining_balita.id_user",
                "=",
                "tbl_users.id_user"
            );
        }]);
        //--district
        if($params['district_id']!=""){
            $query=$query->where("id_region", $params['district_id']);
        }
        //--type, search, order
        $query=$query->where("type", "kecamatan")
            ->where("region", "like", "%".$params['q']."%")
            ->orderBy("region");

        //return
        return $query->get()->toArray();
    }

    public static function gets_stunting($params)
    {
        //params
        $params['per_page']=trim($params['per_page']);
        $params['posyandu_id']=trim($params['posyandu_id']);

        //query
        $query=SkriningBalitaModel::selectRaw("
            max(usia_saat_ukur) as usia_saat_ukur,
            SUBSTRING(max(CONCAT(LPAD(usia_saat_ukur, 11, '0'), COALESCE(`id_skrining_balita`, ''))), 12) as id_skrining_balita, 
            SUBSTRING(max(CONCAT(LPAD(usia_saat_ukur, 11, '0'), COALESCE(`id_user`, ''))), 12) as id_user, 
            SUBSTRING(max(CONCAT(LPAD(usia_saat_ukur, 11, '0'), data_anak)), 12) as data_anak, 
            SUBSTRING(max(CONCAT(LPAD(usia_saat_ukur, 11, '0'), hasil_tinggi_badan_per_umur)), 12) as hasil_tinggi_badan_per_umur,
            SUBSTRING(max(CONCAT(LPAD(usia_saat_ukur, 11, '0'), hasil_berat_badan_per_umur)), 12) as hasil_berat_badan_per_umur,
            SUBSTRING(max(CONCAT(LPAD(usia_saat_ukur, 11, '0'), hasil_berat_badan_per_tinggi_badan)), 12) as hasil_berat_badan_per_tinggi_badan,
            SUBSTRING(max(CONCAT(LPAD(usia_saat_ukur, 11, '0'), tinggi_badan)), 12) as tinggi_badan,
            SUBSTRING(max(CONCAT(LPAD(usia_saat_ukur, 11, '0'), berat_badan)), 12) as berat_badan,
            SUBSTRING(max(CONCAT(LPAD(usia_saat_ukur, 11, '0'), berat_badan_lahir)), 12) as berat_badan_lahir,
            SUBSTRING(max(CONCAT(LPAD(usia_saat_ukur, 11, '0'), tinggi_badan_lahir)), 12) as tinggi_badan_lahir,
            SUBSTRING(max(CONCAT(LPAD(usia_saat_ukur, 11, '0'), created_at)), 12) as created_at
        ")
        ->with("user_posyandu", "user_posyandu.region", "user_posyandu.region.parent")
        ->groupBy(
            \DB::raw("json_unquote(json_extract(`data_anak`, '$.nik'))")
        )
        ->havingRaw("hasil_tinggi_badan_per_umur in ('pendek', 'sangat_pendek')");
        //--posyandu id
        if($params['posyandu_id']!=""){
            $query=$query->having("id_user", $params['posyandu_id']);
        }
        //--q
        $query=$query->having(function($q)use($params){
            return $q->having("data_anak->nik", "like", "%".$params['q']."%")
                ->orHaving("data_anak->nama_lengkap", "like", "%".$params['q']."%");
        });

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

        //return
        return array_merge($data, [
            'data'  =>$new_data
        ]);
    }

    public static function get_region_center($district_id)
    {
        //params
        $district_id=trim($district_id);

        //query
        if($district_id!=""){
            $r=RegionModel::where("id_region", $district_id)->first();

            if(!is_null($r)){
                if($r['data']['map_center']['latitude']!=""&&$r['data']['map_center']['longitude']!=""&&$r['data']['map_center']['zoom']!=""){
                    return $r['data']['map_center'];
                }
            }
        }
        
        return [
            'latitude'  =>'-7.61351',
            'longitude' =>'111.63688',
            'zoom'      =>'11'
        ];
    }
}