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
        Schema::create('tbl_intervensi_realisasi_bantuan', function (Blueprint $table) {
            $table->id("id_realisasi_bantuan");
            $table->unsignedBigInteger("id_skrining_balita")->comment("dari table stunting 4118");
            $table->unsignedBigInteger("id_rencana_bantuan");
            $table->text("dokumen");
            $table->timestamps();

            //fk
            $table->foreign("id_skrining_balita")->references("id_skrining_balita")->on("tbl_stunting_4118")->onDelete("cascade");
            $table->foreign("id_rencana_bantuan")->references("id_rencana_bantuan")->on("tbl_intervensi_rencana_bantuan")->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tbl_intervensi_realisasi_bantuan');
    }
};
