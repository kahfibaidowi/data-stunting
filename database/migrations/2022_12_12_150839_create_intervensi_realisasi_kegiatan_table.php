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
        Schema::create('tbl_intervensi_realisasi_kegiatan', function (Blueprint $table) {
            $table->id("id_realisasi_kegiatan");
            $table->unsignedBigInteger("id_rencana_kegiatan");
            $table->text("dokumen");
            $table->timestamps();

            //fk
            $table->foreign("id_rencana_kegiatan")->references("id_rencana_kegiatan")->on("tbl_intervensi_rencana_kegiatan")->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tbl_intervensi_realisasi_kegiatan');
    }
};
