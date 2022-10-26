<?php

namespace App\Repository;

use App\Models\UserModel;
use App\Models\UserLoginModel;


class UserRepo{
    
    public static function get_user_by_username($username, $return_fields=["*"])
    {
        //query
        $user=UserModel::select($return_fields)
            ->where("username", $username);
        
        //return
        return optional(
            optional($user->first())->makeVisible("password")
        )->toArray();
    }

    public static function get_user($user_id, $return_fields=["*"])
    {
        //query
        $user=UserModel::select($return_fields)->with("region")
            ->where("id_user", $user_id);
        
        //return
        return optional($user->first())->toArray();
    }

    public static function gets_user($params, $return_fields=["*"])
    {
        //params
        $params['per_page']=trim($params['per_page']);
        $params['role']=trim($params['role']);
        $params['status']=trim($params['status']);

        //query
        $users=UserModel::select($return_fields)->with("region");
        //--q
        $users=$users->where(function($query) use($params){
            $query->where("nama_lengkap", "like", "%".$params['q']."%")
                ->orWhere("username", "like", "%".$params['q']."%");
        });
        //--role
        if($params['role']!=""){
            $users=$users->where("role", $params['role']);
        }
        //--status
        if($params['status']!=""){
            $users=$users->where("status", $params['status']);
        }
        //--order
        $users=$users->orderByDesc("id_user");

        //return
        return $users->paginate($params['per_page'])->toArray();
    }
}