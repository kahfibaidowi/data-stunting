<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class BalitaModel extends Model{

    protected $table="tbl_balita";
    protected $primaryKey="id_balita";
    protected $fillable=[
        "nik",
        "no_kk",
        "nama_lengkap",
        "tempat_lahir",
        "tgl_lahir",
        "jenis_kelamin",
        "provinsi",
        "kabupaten_kota",
        "kecamatan",
        "desa",
        "alamat_detail",
        "ibu",
        "data_status",
        "from_kependudukan",
        "berat_badan_lahir",
        "tinggi_badan_lahir"
    ];
    protected $casts=[
        "provinsi"=>"array",
        "kabupaten_kota"=>"array",
        "kecamatan"=>"array",
        "desa"=>"array",
        "alamat_detail"=>"array",
        "ibu"=>"array"
    ];
    protected $hidden=['password'];
    protected $perPage=99999999999999999999;


    /*
     *#FUNCTION
     *
     */
}
