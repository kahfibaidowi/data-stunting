<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class IntervensiRealisasiKegiatanModel extends Model{

    protected $table="tbl_intervensi_realisasi_kegiatan";
    protected $primaryKey="id_realisasi_kegiatan";
    protected $fillable=[
        "id_user",
        "tahun",
        "kegiatan",
        "sasaran",
        "anggaran",
        "satuan",
        "detail_kegiatan",
        "jumlah"
    ];
    protected $perPage=99999999999999999999;


    /*
     *#FUNCTION
     *
     */
    public function user(){
        return $this->belongsTo(UserModel::class, "id_user");
    }
}
