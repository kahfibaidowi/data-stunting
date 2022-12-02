<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class IntervensiRencanaBantuanModel extends Model{

    protected $table="tbl_intervensi_rencana_bantuan";
    protected $primaryKey="id_rencana_bantuan";
    protected $fillable=[
        "id_user",
        "tahun",
        "bantuan",
        "harga_satuan",
        "satuan",
        "qty",
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
