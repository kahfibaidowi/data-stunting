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
        Schema::create('tbl_region', function (Blueprint $table) {
            $table->id("id_region");
            $table->unsignedBigInteger("nested")->nullable()->comment("turunan id_region");
            $table->text("type");
            $table->text("region");
            $table->timestamps();

            //fk
            $table->foreign("nested")
                ->references("id_region")
                ->on("tbl_region")
                ->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tbl_region');
    }
};
