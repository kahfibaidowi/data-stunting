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
    protected $perPage=99999999999999999999;


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

    public function skrining_balita(){
        return $this->hasMany(SkriningBalitaModel::class, "id_user");
    }

    public function intervensi_realisasi_kegiatan(){
        return $this->hasManyThrough(
            IntervensiRealisasiKegiatanModel::class,
            IntervensiRencanaKegiatanModel::class,
            "id_user",
            "id_rencana_kegiatan",
            "id_user",
            "id_rencana_kegiatan"
        );
    }

    public function intervensi_realisasi_bantuan(){
        return $this->hasManyThrough(
            IntervensiRealisasiBantuanModel::class,
            IntervensiRencanaBantuanModel::class,
            "id_user",
            "id_rencana_bantuan",
            "id_user",
            "id_rencana_bantuan"
        );
    }

    public function intervensi_rencana_kegiatan(){
        return $this->hasMany(IntervensiRencanaKegiatanModel::class, "id_user");
    }

    public function intervensi_rencana_bantuan(){
        return $this->hasMany(IntervensiRencanaBantuanModel::class, "id_user");
    }
}
