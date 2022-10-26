<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\UserModel;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //USER
        UserModel::create([
            'id_region' =>null,
            'username'  =>"admin",
            'password'  =>Hash::make("admin"),
            'avatar_url'=>"",
            'role'      =>"admin",
            "nama_lengkap"=>"Super Admin",
            "status"    =>"active"
        ]);
    }
}
