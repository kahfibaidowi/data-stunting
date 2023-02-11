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
        Schema::create('tbl_skrining_balita', function (Blueprint $table) {
            $table->id("id_skrining_balita");
            $table->unsignedBigInteger("id_user")->nullable()->comment("user posyandu");
            $table->text("data_anak")->comment("diambil dari data-kependudukan");
            $table->double("berat_badan_lahir");
            $table->double("tinggi_badan_lahir");
            $table->integer("usia_saat_ukur");
            $table->double("berat_badan");
            $table->double("tinggi_badan");
            $table->text("hasil_tinggi_badan_per_umur");
            $table->text("hasil_berat_badan_per_umur");
            $table->text("hasil_berat_badan_per_tinggi_badan");
            $table->text("hasil_status_gizi");
            $table->timestamps();

            
            //fk
            $table->foreign("id_user")->references("id_user")->on("tbl_users")->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tbl_skrining_balita');
    }
};
