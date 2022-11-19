<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserLoginModel extends Model{

    protected $table="tbl_users_login";
    protected $primaryKey="id_user_login";
    protected $fillable=[
        'id_user', 
        'login_token', 
        'expired', 
        'last_online', 
        'device_info'
    ];
    protected $hidden=['login_token'];
    protected $perPage=99999999999999999999;

    protected $appends=['expired'];

    /*
     *#CUSTOM RETURN ATTRIBUTE
     *
     */
    public function getExpiredAttribute(){
        $value=$this->attributes['expired'];
        return Carbon::parse($value)
            ->timezone(env("APP_TIMEZONE"));
    }

    /*
     *#FUNCTION
     *
     */
    public function user(){
        return $this->belongsTo(UserModel::class, "id_user");
    }
}