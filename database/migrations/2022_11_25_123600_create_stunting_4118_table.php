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
        Schema::create('tbl_stunting_4118', function (Blueprint $table) {
            $table->id("id_skrining_balita");
            $table->unsignedBigInteger("id_user")->nullable()->comment("user posyandu");
            $table->unsignedBigInteger("id_kecamatan")->nullable()->comment("kecamatan");
            $table->text("data_anak")->default("{}");
            $table->double("berat_badan_lahir")->nullable();
            $table->double("tinggi_badan_lahir")->nullable();
            $table->integer("usia_saat_ukur")->nullable();
            $table->double("berat_badan")->nullable();
            $table->double("tinggi_badan")->nullable();
            $table->text("hasil_tinggi_badan_per_umur");
            $table->text("hasil_berat_badan_per_umur");
            $table->text("hasil_berat_badan_per_tinggi_badan");
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
        Schema::dropIfExists('tbl_stunting_4118');
    }
};
