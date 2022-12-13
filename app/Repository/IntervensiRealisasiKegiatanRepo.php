<?php

namespace App\Repository;

use App\Models\IntervensiRealisasiKegiatanModel;


class IntervensiRealisasiKegiatanRepo{
    
    public static function get_kegiatan($kegiatan_id)
    {
        //query
        $query=IntervensiRealisasiKegiatanModel::where("id_realisasi_kegiatan", $kegiatan_id);
        
        //return
        return optional($query->first())->toArray();
    }

    public static function gets_kegiatan($params)
    {
        //params
        $params['per_page']=trim($params['per_page']);

        //query
        $query=IntervensiRealisasiKegiatanModel::query();
        //--q
        $query=$query->where(function($q)use($params){
            return $q->where("kegiatan", "like", "%".$params['q']."%")
                ->orWhere("detail_kegiatan", "like", "%".$params['q']."%");
        });
        //--id user
        $query=$query->where("id_user", $params['id_user']);
        //--tahun
        $query=$query->where("tahun", $params['tahun']);
        //--order
        $query=$query->orderByDesc("id_realisasi_kegiatan");

        //return
        return $query->paginate($params['per_page'])->toArray();
    }
}