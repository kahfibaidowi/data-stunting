<?php

namespace App\Repository;

use App\Models\UserModel;
use App\Models\UserLoginModel;


class UserLoginRepo{
    
    public static function gets_user_login($params, $return_fields=["*"])
    {
        //params
        $params['per_page']=trim($params['per_page']);
        $params['token_status']=trim($params['token_status']);

        //query
        $users_login=UserLoginModel::select($return_fields);
        //--q[nama_lengkap]
        $users_login=$users_login->whereHas("user", function($q) use($params){
            $q->where("nama_lengkap", "like", "%".$params['q']."%");
        });
        //--token status
        switch($params['token_status']){
            case "expired":
                $users_login=$users_login->where("expired", "<", date("Y-m-d H:i:s"));
            break;
            case "not_expired":
                $users_login=$users_login->where("expired", ">=", date("Y-m-d H:i:s"));
            break;
        }
        //--order
        $users_login=$users_login->orderByDesc("id_user_login");

        //return
        return $users_login->paginate($params['per_page'])->toArray();
    }

    public static function get_by_user($user_id, $return_fields=["*"])
    {
        //query
        $users_login=UserLoginModel::select($return_fields)
            ->where("id_user", $user_id);
        
        //return
        return $users_login->get()->makeVisible("login_token")->toArray();
    }
}