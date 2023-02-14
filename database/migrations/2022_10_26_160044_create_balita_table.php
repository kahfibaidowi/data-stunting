<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_balita', function (Blueprint $table) {
            $table->id("id_balita");
            $table->string("nik", 100)->unique();
            $table->string("no_kk", 100)->nullable();
            $table->text("nama_lengkap")->nullable();
            $table->text("tempat_lahir")->nullable();
            $table->date("tgl_lahir")->nullable();
            $table->text("jenis_kelamin")->nullable();
            $table->text("provinsi")->nullable();
            $table->text("kabupaten_kota")->nullable();
            $table->text("kecamatan")->nullable();
            $table->text("desa")->nullable();
            $table->text("alamat_detail")->nullable();
            $table->text("ibu")->nullable();
            $table->text("data_status")->nullable();
            $table->text("from_kependudukan");
            $table->double("berat_badan_lahir")->nullable();
            $table->double("tinggi_badan_lahir")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tbl_balita');
    }
};
