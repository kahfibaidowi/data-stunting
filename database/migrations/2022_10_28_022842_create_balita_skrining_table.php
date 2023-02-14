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
        Schema::create('tbl_balita_skrining', function (Blueprint $table) {
            $table->id("id_balita_skrining");
            $table->unsignedBigInteger("id_user")->nullable()->comment("user posyandu");
            $table->unsignedBigInteger("id_balita");
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
            $table->foreign("id_balita")->references("id_balita")->on("tbl_balita")->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tbl_balita_skrining');
    }
};
