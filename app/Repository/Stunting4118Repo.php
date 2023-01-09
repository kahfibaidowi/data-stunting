<?php

namespace App\Repository;


use App\Models\Stunting4118Model;
use App\Models\RegionModel;
use App\Models\UserModel;


class Stunting4118Repo{

    public static function gets_skrining($params)
    {
        //params
        $params['per_page']=trim($params['per_page']);
        $params['district_id']=trim($params['district_id']);

        //query
        $query=Stunting4118Model::with("kecamatan:id_region,region,type", "user_posyandu", "user_posyandu.region", "user_posyandu.region.parent");
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

    public static function gets_sebaran_bantuan($params)
    {
        //params
        $params['per_page']=trim($params['per_page']);
        $params['tahun']=trim($params['tahun']);

        //query
        $query=RegionModel::where("type", "kecamatan");
        //--q
        $query=$query->where("region", "like", "%".$params['q']."%");
        //--count stunting
        $query=$query->withCount("stunting_4118_kecamatan as count_stunting");
        $query=$query->withCount(["realisasi_bantuan_kecamatan as count_penerima_bantuan"=>function($q)use($params){
            if($params['tahun']!=""){
                $q->select(\DB::raw("count(distinct(tbl_intervensi_realisasi_bantuan.id_skrining_balita))"))->whereHas("rencana_bantuan", function($q2)use($params){
                    $q2->where("tahun", $params['tahun']);
                });
            }
            $q->select(\DB::raw("count(distinct(tbl_intervensi_realisasi_bantuan.id_skrining_balita))"));
        }]);
        //--count total bantuan
        $query=$query->withSum(
            ["realisasi_bantuan_kecamatan as total_bantuan"=>function($q)use($params){
                $q->join("tbl_intervensi_rencana_bantuan", "tbl_intervensi_realisasi_bantuan.id_rencana_bantuan", "=", "tbl_intervensi_rencana_bantuan.id_rencana_bantuan")
                    ->select(\DB::raw('COALESCE(SUM(tbl_intervensi_rencana_bantuan.harga_satuan), 0)'));
                if($params['tahun']!=""){
                    $q->where("tbl_intervensi_rencana_bantuan.tahun", $params['tahun']);
                }
            }],
            "harga_satuan"
        );

        //return
        return $query->paginate($params['per_page'])->toArray();
    }

    public static function gets_anggaran($params)
    {
        //params
        $params['per_page']=trim($params['per_page']);
        $params['tahun']=trim($params['tahun']);

        //query
        $query=UserModel::where("role", "dinas");
        //--q
        $query=$query->where("nama_lengkap" , "like", "%".$params['q']."%");
        //--total realisasi kegiatan
        $query=$query->withSum(
            ["intervensi_realisasi_kegiatan as total_realisasi_kegiatan"=>function($q)use($params){
                if($params['tahun']!=""){
                    $q->select(\DB::raw('COALESCE(SUM(tbl_intervensi_rencana_kegiatan.jumlah), 0)'))
                        ->where("tbl_intervensi_rencana_kegiatan.tahun", $params['tahun']);
                }
                $q->select(\DB::raw('COALESCE(SUM(tbl_intervensi_rencana_kegiatan.jumlah), 0)'));
            }],
            "jumlah"
        );
        //--total realisasi bantuan
        $query=$query->withSum(
            ["intervensi_realisasi_bantuan as total_realisasi_bantuan"=>function($q)use($params){
                if($params['tahun']!=""){
                    $q->select(\DB::raw('COALESCE(SUM(tbl_intervensi_rencana_bantuan.harga_satuan), 0)'))
                        ->where("tbl_intervensi_rencana_bantuan.tahun", $params['tahun']);
                }
                $q->select(\DB::raw('COALESCE(SUM(tbl_intervensi_rencana_bantuan.harga_satuan), 0)'));
            }],
            "harga_satuan"
        );
        //--total rencana kegiatan
        $query=$query->withSum(
            ["intervensi_rencana_kegiatan as total_rencana_kegiatan"=>function($q)use($params){
                if($params['tahun']!=""){
                    $q->select(\DB::raw('COALESCE(SUM(jumlah), 0)'))
                        ->where("tahun", $params['tahun']);
                }
                $q->select(\DB::raw('COALESCE(SUM(jumlah), 0)'));
            }],
            "jumlah"
        );
        //--total rencana bantuan
        $query=$query->withSum(
            ["intervensi_rencana_bantuan as total_rencana_bantuan"=>function($q)use($params){
                if($params['tahun']!=""){
                    $q->select(\DB::raw('COALESCE(SUM(jumlah), 0)'))
                        ->where("tahun", $params['tahun']);
                }
                $q->select(\DB::raw('COALESCE(SUM(jumlah), 0)'));
            }],
            "jumlah"
        );
        //--order
        $query=$query->orderBy("nama_lengkap");

        //return
        return $query->paginate($params['per_page'])->toArray();
    }
}