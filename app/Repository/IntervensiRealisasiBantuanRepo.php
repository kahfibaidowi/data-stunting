<?php

namespace App\Repository;

use App\Models\IntervensiRealisasiBantuanModel;
use App\Models\UserModel;


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

    public static function gets_realisasi_bantuan_dinas_by_tahun($params)
    {
        $year=date("Y");
        $years=[];
        for($i=$year-$params['last_year']; $i<=$year; $i++){
            $years[]=$i;
        }

        //query
        $query=IntervensiRealisasiBantuanModel::join("tbl_intervensi_rencana_bantuan", "tbl_intervensi_realisasi_bantuan.id_rencana_bantuan", "=", "tbl_intervensi_rencana_bantuan.id_rencana_bantuan")
            ->select(\DB::raw("sum(tbl_intervensi_rencana_bantuan.harga_satuan) as total_bantuan, tbl_intervensi_rencana_bantuan.tahun"))
            ->whereIn("tbl_intervensi_rencana_bantuan.tahun", $years)
            ->groupBy("tbl_intervensi_rencana_bantuan.tahun");

        //data
        $bantuan=$query->get()->toArray();
        $data=[];
        foreach($years as $val){
            $find=array_find_by_key($bantuan, "tahun", $val);
            if($find!==false){
                $data[]=$find;
            }
            else{
                $data[]=[
                    'total_bantuan' =>0,
                    'tahun'         =>$val
                ];
            }
        }

        //return
        return $data;
    }

    public static function gets_realisasi_bantuan_tahun_by_dinas($params)
    {
        //params=
        $params['tahun']=trim($params['tahun']);

        //query
        $query=UserModel::where("role", "dinas")
            ->withSum(
                ["intervensi_realisasi_bantuan as total_realisasi_bantuan"=>function($q)use($params){
                    if($params['tahun']!=""){
                        $q->select(\DB::raw('COALESCE(SUM(tbl_intervensi_rencana_bantuan.harga_satuan), 0)'))
                            ->where("tbl_intervensi_rencana_bantuan.tahun", $params['tahun']);
                    }
                    $q->select(\DB::raw('COALESCE(SUM(tbl_intervensi_rencana_bantuan.harga_satuan), 0)'));
                }],
                "harga_satuan"
            );
        //--order
        $query=$query->orderBy("nama_lengkap");

        //return
        return $query->get()->toArray();
    }
}