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
        Schema::create('tbl_intervensi_rencana_bantuan', function (Blueprint $table) {
            $table->id("id_rencana_bantuan");
            $table->unsignedBigInteger("id_user")->comment("created by");
            $table->integer("tahun");
            $table->text("bantuan");
            $table->double("harga_satuan");
            $table->text("satuan");
            $table->double("qty");
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
        Schema::dropIfExists('tbl_intervensi_rencana_bantuan');
    }
};
