<?php

namespace App\Repository;

use App\Models\IntervensiRealisasiKegiatanModel;


class IntervensiRealisasiKegiatanRepo{
    
    public static function get_kegiatan($kegiatan_id)
    {
        //query
        $query=IntervensiRealisasiKegiatanModel::with("rencana_kegiatan")->where("id_realisasi_kegiatan", $kegiatan_id);
        
        //return
        return optional($query->first())->toArray();
    }

    public static function gets_kegiatan($params)
    {
        //params
        $params['per_page']=trim($params['per_page']);

        //query
        $query=IntervensiRealisasiKegiatanModel::with("rencana_kegiatan");
        $query=$query->whereHas("rencana_kegiatan", function($q)use($params){
            return $q->where("id_user", $params['id_user'])
                ->where("tahun", $params['tahun'])
                ->where(function($q2)use($params){
                    return $q2->where("kegiatan", "like", "%".$params['q']."%")
                    ->orWhere("detail_kegiatan", "like", "%".$params['q']."%");
                });
        });
        //--order
        $query=$query->orderByDesc("id_realisasi_kegiatan");

        //return
        return $query->paginate($params['per_page'])->toArray();
    }
}