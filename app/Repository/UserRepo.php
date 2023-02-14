<?php

namespace App\Repository;

use App\Models\UserModel;
use App\Models\UserLoginModel;


class UserRepo{
    
    public static function get_user_by_username($username)
    {
        //query
        $query=UserModel::where("username", $username);
        
        //return
        return optional(
            optional($query->first())->makeVisible("password")
        )->toArray();
    }

    public static function get_user($user_id)
    {
        //query
        $query=UserModel::with("region")->where("id_user", $user_id);
        
        //return
        return optional($query->first())->toArray();
    }

    public static function gets_user($params)
    {
        //params
        $params['per_page']=trim($params['per_page']);
        $params['role']=trim($params['role']);
        $params['status']=trim($params['status']);

        //query
        $query=UserModel::with("region");
        //--q
        $query=$query->where(function($query) use($params){
            $query->where("nama_lengkap", "like", "%".$params['q']."%")
                ->orWhere("username", "like", "%".$params['q']."%");
        });
        //--role
        if($params['role']!=""){
            $query=$query->where("role", $params['role']);
        }
        //--status
        if($params['status']!=""){
            $query=$query->where("status", $params['status']);
        }
        //--order
        $query=$query->orderByDesc("id_user");

        //return
        return $query->paginate($params['per_page'])->toArray();
    }

    public static function gets_data_masuk($params)
    {
        //params
        $params['per_page']=trim($params['per_page']);

        //query
        $query=UserModel::with("region:id_region,nested,region", "region.parent:id_region,nested,region");
        $query=$query->withCount(["skrining_balita as count_balita"=>function($q){
            return $q->select(\DB::raw("count(distinct(json_unquote(json_extract(`data_anak`, '$.nik'))))"));
        }]);
        $query=$query->where("role", "posyandu");
        $query=$query->where("nama_lengkap", "like", "%".$params['q']."%");

        $data=$query->paginate($params['per_page'])->toArray();

        $new_data=[];
        foreach($data['data'] as $val){
            $new_data[]=array_merge_without($val, ['region'], [
                'desa'  =>$val['region']['region'],
                'kecamatan' =>$val['region']['parent']['region']
            ]);
        }

        return array_merge($data, [
            'data'  =>$new_data
        ]);
    }
}