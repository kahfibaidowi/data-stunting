<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class RegionModel extends Model{

    protected $table="tbl_region";
    protected $primaryKey="id_region";
    protected $fillable=[
        "nested",
        "type",
        "region"
    ];


    /*
     *#FUNCTION
     *
     */
    public function desa(){
        return $this->hasMany(RegionModel::class, "nested", "id_region")
            ->where("type", "desa")
            ->orderBy("id_region");
    }

    public function posyandu(){
        return $this->hasMany(UserModel::class, "id_region");
    }
    
    public function parent(){
        return $this->belongsTo(RegionModel::class, "nested", "id_region");
    }
}
