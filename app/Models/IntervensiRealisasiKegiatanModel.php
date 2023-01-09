<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class IntervensiRealisasiKegiatanModel extends Model{

    protected $table="tbl_intervensi_realisasi_kegiatan";
    protected $primaryKey="id_realisasi_kegiatan";
    protected $fillable=[
        "id_user",
        "id_rencana_kegiatan",
        "dokumen"
    ];
    protected $perPage=99999999999999999999;


    /*
     *#FUNCTION
     *
     */
    public function rencana_kegiatan(){
        return $this->belongsTo(IntervensiRencanaKegiatanModel::class, "id_rencana_kegiatan");
    }
}
