<?php

namespace App\Repository;

use App\Models\UserModel;
use App\Models\UserLoginModel;


class UserLoginRepo{
    
    public static function gets_user_login($params)
    {
        //params
        $params['per_page']=trim($params['per_page']);
        $params['token_status']=trim($params['token_status']);

        //query
        $query=UserLoginModel::query();
        //--q[nama_lengkap]
        $query=$query->whereHas("user", function($q) use($params){
            $q->where("nama_lengkap", "like", "%".$params['q']."%");
        });
        //--token status
        switch($params['token_status']){
            case "expired":
                $query=$query->where("expired", "<", date("Y-m-d H:i:s"));
            break;
            case "not_expired":
                $query=$query->where("expired", ">=", date("Y-m-d H:i:s"));
            break;
        }
        //--order
        $query=$query->orderByDesc("id_user_login");

        //return
        return $query->paginate($params['per_page'])->toArray();
    }

    public static function get_by_user($user_id)
    {
        //query
        $query=UserLoginModel::where("id_user", $user_id);
        
        //return
        return $query->get()->makeVisible("login_token")->toArray();
    }
}