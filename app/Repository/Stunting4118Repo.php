<?php

namespace App\Repository;


use App\Models\Stunting4118Model;
use App\Models\RegionModel;


class Stunting4118Repo{

    public static function gets_skrining($params)
    {
        //params
        $params['per_page']=trim($params['per_page']);
        $params['district_id']=trim($params['district_id']);

        //query
        $query=Stunting4118Model::with("kecamatan", "user_posyandu", "user_posyandu.region", "user_posyandu.region.parent");
        //--district id
        if($params['district_id']!=""){
            $query=$query->where("id_kecamatan", $params['district_id']);
        }
        //--q
        $query=$query->where(function($q)use($params){
            return $q->where("data_anak->nik", "like", "%".$params['q']."%")
                ->orWhere("data_anak->nama_lengkap", "like", "%".$params['q']."%");
        });
        //--order
        $query=$query->orderBy("id_skrining_balita");

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
    
    public static function gets_stunting_by_region_kecamatan()
    {
        //params

        //query
        //--count stunting
        $query=RegionModel::withCount(["stunting_4118_kecamatan as count_stunting"]);
        //--type, order 
        $query=$query->where("type", "kecamatan")->orderBy("region");

        //return
        return $query->get()->toArray();
    }

    public static function get_region_center()
    {
        //return
        return [
            'latitude'  =>'-7.61351',
            'longitude' =>'111.63688',
            'zoom'      =>'11'
        ];
    }
}