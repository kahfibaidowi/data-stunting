<?php

namespace App\Repository;

use App\Models\RegionModel;


class RegionRepo{
    
    public static function gets_kecamatan($params)
    {
        //params
        $params['per_page']=trim($params['per_page']);

        //query
        $query=RegionModel::query();
        //--with desa
        if($params['with_desa']){
            $query=$query->with("desa");
        }
        //--with posyandu
        if($params['with_posyandu']){
            $query=$query->with("desa.posyandu");
        }
        $query=$query->where("type", "kecamatan")
            ->where("region", "like", "%".$params['q']."%")
            ->orderBy("region");
        
        //return
        return $query->paginate($params['per_page'])->toArray();
    }

    public static function gets_desa($params)
    {
        //params
        $params['per_page']=trim($params['per_page']);

        //query
        $query=RegionModel::where("type", "desa")
            ->where("nested", $params['district_id'])
            ->where("region", "like", "%".$params['q']."%")
            ->orderBy("region");
        
        //return
        return $query->paginate($params['per_page'])->toArray();
    }

    public static function get_region($region_id)
    {
        //query
        $query=RegionModel::where("id_region", $region_id);

        //return
        return optional($query->first())->toArray();
    }
}