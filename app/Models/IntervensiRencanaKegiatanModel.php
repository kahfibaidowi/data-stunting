<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class IntervensiRencanaKegiatanModel extends Model{

    protected $table="tbl_intervensi_rencana_kegiatan";
    protected $primaryKey="id_rencana_kegiatan";
    protected $fillable=[
        "id_user",
        "tahun",
        "kegiatan",
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
