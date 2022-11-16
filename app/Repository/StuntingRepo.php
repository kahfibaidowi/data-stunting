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
        $query=RegionModel::with(["posyandu"=>function($q){
            return $q->withCount(["skrining_balita as count_stunting"=>function($q2){
                return $q2->whereIn("hasil_tinggi_badan_per_umur", ["sangat_pendek", "pendek"]);
            }]);
        }]);
        //--district
        if($params['district_id']!=""){
            $query=$query->where("nested", $params['district_id']);
        }
        //--type, search, order
        $query=$query->where("type", "desa")
            ->where("region", "like", "%".$params['q']."%")
            ->orderBy("region");

        //data
        $data=$query->get()->toArray();

        $new_data=[];
        foreach($data as $val){
            $count_stunting=0;
            foreach($val['posyandu'] as $posy){
                $count_stunting+=$posy['count_stunting'];
            }

            $new_data[]=array_merge_without($val, ['posyandu'], [
                'count_stunting'=>$count_stunting
            ]);
        }

        //return
        return $new_data;
    }

    public static function gets_stunting_by_region_kecamatan($params)
    {
        //params
        $params['district_id']=trim($params['district_id']);

        //query
        //--count stunting
        $query=RegionModel::with(['desa'=>function($qq){
            return $qq->with(["posyandu"=>function($q){
                return $q->withCount(["skrining_balita as count_stunting"=>function($q2){
                    return $q2->whereIn("hasil_tinggi_badan_per_umur", ["sangat_pendek", "pendek"]);
                }]);
            }]);
        }]);
        //--district
        if($params['district_id']!=""){
            $query=$query->where("id_region", $params['district_id']);
        }
        //--type, search, order
        $query=$query->where("type", "kecamatan")
            ->where("region", "like", "%".$params['q']."%")
            ->orderBy("region");

        //data
        $data=$query->get()->toArray();

        $new_data=[];
        foreach($data as $val){
            $count_stunting=0;
            foreach($val['desa'] as $desa){
                foreach($desa['posyandu'] as $posy){
                    $count_stunting+=$posy['count_stunting'];
                }
            }

            $new_data[]=array_merge_without($val, ['desa'], [
                'count_stunting'=>$count_stunting
            ]);


        }

        //return
        return $new_data;
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
            'zoom'      =>'12'
        ];
    }
}