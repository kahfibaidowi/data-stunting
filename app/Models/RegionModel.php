<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class RegionModel extends Model{

    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;
    use \Staudenmeir\EloquentHasManyDeep\HasTableAlias;

    protected $table="tbl_region";
    protected $primaryKey="id_region";
    protected $fillable=[
        "nested",
        "type",
        "region",
        "data"
    ];
    protected $casts=[
        "data"=>"array"
    ];
    protected $perPage=99999999999999999999;


    /*
     *#FUNCTION
     *
     */
    public function desa(){
        return $this->hasMany(RegionModel::class, "nested", "id_region")->where("type", "desa")->orderBy("id_region");
    }

    public function posyandu(){
        return $this->hasMany(UserModel::class, "id_region");
    }

    public function posyandu_kecamatan(){
        return $this->hasManyThrough(
            UserModel::class,
            RegionModel::class,
            "nested",
            "id_region",
            "id_region",
            "id_region"
        );
    }
    
    public function parent(){
        return $this->belongsTo(RegionModel::class, "nested", "id_region");
    }

    public function skrining_balita_kecamatan(){
        return $this->hasManyDeep(
            SkriningBalitaModel::class,
            [RegionModel::class, UserModel::class],
            [
                'nested',
                'id_region',
                'id_user'
            ],
            [
                'id_region',
                'id_region',
                'id_user'
            ]
        );
    }

    public function skrining_balita_desa(){
        return $this->hasManyThrough(
            SkriningBalitaModel::class,
            UserModel::class,
            'id_region',
            'id_user',
            'id_region',
            'id_user'
        );
    }

    public function stunting_4118_kecamatan(){
        return $this->hasMany(Stunting4118Model::class, "id_kecamatan", "id_region");
    }

    public function realisasi_bantuan_kecamatan(){
        return $this->hasManyThrough(
            IntervensiRealisasiBantuanModel::class,
            Stunting4118Model::class,
            "id_kecamatan",
            "id_skrining_balita",
            "id_region",
            "id_skrining_balita"
        );
    }
}
