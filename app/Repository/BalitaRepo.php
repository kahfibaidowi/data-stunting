<?php

namespace App\Repository;

use App\Models\BalitaModel;


class BalitaRepo{

    public static function get_balita($skrining_id, $column)
    {
        //query
        $query=BalitaModel::where($column, $skrining_id)->orderByDesc("id_balita");

        //return
        return optional($query->first())->toArray();
    }
}