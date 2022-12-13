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
            $table->unsignedBigInteger("id_user")->comment("created by");
            $table->integer("tahun");
            $table->text("kegiatan");
            $table->text("sasaran");
            $table->double("anggaran");
            $table->text("satuan");
            $table->text("detail_kegiatan");
            $table->double("jumlah");
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
        Schema::dropIfExists('tbl_intervensi_realisasi_kegiatan');
    }
};
