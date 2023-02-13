<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class BalitaModel extends Model{

    protected $table="tbl_skrining_balita";
    protected $primaryKey="id_skrining_balita";
    protected $fillable=[
        "id_user",
        "data_anak",
        "berat_badan_lahir",
        "tinggi_badan_lahir",
        "usia_saat_ukur",
        "berat_badan",
        "tinggi_badan",
        "hasil_tinggi_badan_per_umur",
        "hasil_berat_badan_per_umur",
        "hasil_berat_badan_per_tinggi_badan",
        "hasil_status_gizi"
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
