<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class IntervensiRealisasiBantuanModel extends Model{

    protected $table="tbl_intervensi_realisasi_bantuan";
    protected $primaryKey="id_realisasi_bantuan";
    protected $fillable=[
        "id_skrining_balita",
        "id_rencana_bantuan",
        "nominal",
        "dokumen"
    ];
    protected $perPage=99999999999999999999;


    /*
     *#FUNCTION
     *
     */
    public function skrining_balita(){
        return $this->belongsTo(Stunting4118Model::class, "id_skrining_balita");
    }
    public function rencana_bantuan(){
        return $this->belongsTo(IntervensiRencanaBantuanModel::class, "id_rencana_bantuan");
    }
}
