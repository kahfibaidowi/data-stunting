<?php

namespace App\Repository;

use App\Models\IntervensiRencanaBantuanModel;


class IntervensiRencanaBantuanRepo{
    
    public static function get_bantuan($bantuan_id)
    {
        //query
        $query=IntervensiRencanaBantuanModel::where("id_rencana_bantuan", $bantuan_id);
        
        //return
        return optional($query->first())->toArray();
    }

    public static function gets_bantuan($params)
    {
        //params
        $params['per_page']=trim($params['per_page']);

        //query
        $query=IntervensiRencanaBantuanModel::query();
        //--q
        $query=$query->where(function($q)use($params){
            return $q->where("bantuan", "like", "%".$params['q']."%")
                ->orWhere("detail_kegiatan", "like", "%".$params['q']."%");
        });
        //--id user
        $query=$query->where("id_user", $params['id_user']);
        //--tahun
        $query=$query->where("tahun", $params['tahun']);
        //--order
        $query=$query->orderByDesc("id_rencana_bantuan");

        //return
        return $query->paginate($params['per_page'])->toArray();
    }
}