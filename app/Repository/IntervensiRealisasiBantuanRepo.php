<?php

namespace App\Repository;

use App\Models\IntervensiRealisasiBantuanModel;


class IntervensiRealisasiBantuanRepo{
    
    public static function get_bantuan($bantuan_id)
    {
        //query
        $query=IntervensiRealisasiBantuanModel::with("skrining_balita", "rencana_bantuan")->where("id_realisasi_bantuan", $bantuan_id);
        
        //return
        return optional($query->first())->toArray();
    }

    public static function gets_bantuan($params)
    {
        //params
        $params['per_page']=trim($params['per_page']);

        //query
        $query=IntervensiRealisasiBantuanModel::with("skrining_balita", "skrining_balita.kecamatan:id_region,region", "rencana_bantuan");
        //--skrining balita
        $query=$query->whereHas("skrining_balita", function($q)use($params){
            return $q->whereRaw("json_unquote(json_extract(`tbl_stunting_4118`.`data_anak`, '$.nik')) like ?", ["%".$params['q']."%"])
                ->orWhereRaw("json_unquote(json_extract(`tbl_stunting_4118`.`data_anak`, '$.nama_lengkap')) like ?", ["%".$params['q']."%"]);
        });
        //--rencana bantuan
        $query=$query->whereHas("rencana_bantuan", function($q)use($params){
            return $q->where("id_user", $params['id_user'])
                ->where("tahun", $params['tahun']);
        });
        //--order
        $query=$query->orderByDesc("id_realisasi_bantuan");

        //return
        return $query->paginate($params['per_page'])->toArray();
    }
}