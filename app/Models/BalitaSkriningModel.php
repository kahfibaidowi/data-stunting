<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class BalitaSkriningModel extends Model{

    protected $table="tbl_balita_skrining";
    protected $primaryKey="id_balita_skrining";
    protected $fillable=[
        "id_user",
        "id_balita",
        "usia_saat_ukur",
        "berat_badan",
        "tinggi_badan",
        "hasil_tinggi_badan_per_umur",
        "hasil_berat_badan_per_umur",
        "hasil_berat_badan_per_tinggi_badan",
        "hasil_status_gizi"
    ];
    protected $perPage=99999999999999999999;


    /*
     *#FUNCTION
     *
     */
    public function user_posyandu(){
        return $this->belongsTo(UserModel::class, "id_user")->where("role", "posyandu");
    }
    public function balita(){
        return $this->belongsTo(BalitaModel::class, "id_balita");
    }
}
