<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class UserModel extends Model{

    protected $table="tbl_users";
    protected $primaryKey="id_user";
    protected $fillable=[
        "id_region",
        "username",
        "email",
        "password",
        "nama_lengkap",
        "role",
        "avatar_url",
        "status"
    ];
    protected $hidden=['password'];


    /*
     *#FUNCTION
     *
     */
    public function user_login(){
        return $this->hasMany(UserLoginModel::class, "id_user");
    }

    public function region(){
        return $this->belongsTo(RegionModel::class, "id_region");
    }
}
