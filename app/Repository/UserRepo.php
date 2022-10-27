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
}