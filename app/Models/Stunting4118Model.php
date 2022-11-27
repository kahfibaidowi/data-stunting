<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Stunting4118Model extends Model{

    protected $table="tbl_stunting_4118";
    protected $primaryKey="id_skrining_balita";
    protected $fillable=[
        "id_kecamatan",
        "id_user",
        "data_anak",
        "berat_badan_lahir",
        "tinggi_badan_lahir",
        "usia_saat_ukur",
        "berat_badan",
        "tinggi_badan",
        "hasil_tinggi_badan_per_umur",
        "hasil_berat_badan_per_umur",
        "hasil_berat_badan_per_tinggi_badan"
    ];
    protected $casts=[
        "data_anak" =>"array"
    ];
    protected $perPage=99999999999999999999;


    /*
     *#FUNCTION
     *
     */
    public function user_posyandu(){
        return $this->belongsTo(UserModel::class, "id_user")
            ->where("role", "posyandu");
    }
}
