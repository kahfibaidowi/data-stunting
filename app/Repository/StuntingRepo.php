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
        $query=RegionModel::withCount(['skrining_balita_desa as count_stunting'=>function($q){
            return $q->whereIn("id_skrining_balita", function($sq){
                $sq->select(\DB::raw("max(id_skrining_balita) as last_update_id FROM `tbl_skrining_balita` group by json_unquote(json_extract(`data_anak`, '$.nik')) having SUBSTRING(max(CONCAT(LPAD(id_skrining_balita, 20, '0'), hasil_tinggi_badan_per_umur)), 21) in ('pendek', 'sangat_pendek')"));
            });
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
        $query=RegionModel::withCount(["skrining_balita_kecamatan as count_stunting"=>function($q){
            return $q->whereIn("id_skrining_balita", function($sq){
                $sq->select(\DB::raw("max(id_skrining_balita) as last_update_id FROM `tbl_skrining_balita` group by json_unquote(json_extract(`data_anak`, '$.nik')) having SUBSTRING(max(CONCAT(LPAD(id_skrining_balita, 20, '0'), hasil_tinggi_badan_per_umur)), 21) in ('pendek', 'sangat_pendek')"));
            });
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