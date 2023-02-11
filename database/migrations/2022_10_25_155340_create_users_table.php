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
        Schema::create('tbl_users', function (Blueprint $table) {
            $table->id("id_user");
            $table->unsignedBigInteger("id_region")->nullable()->comment("referensi desa untuk posyandu");
            $table->string("username", 200)->unique();
            $table->text("password");
            $table->text("nama_lengkap");
            $table->text("role");
            $table->text("avatar_url");
            $table->string("status", 100)->default("active");
            $table->timestamps();
            
            //fk
            $table->foreign("id_region")->references("id_region")->on("tbl_region")->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tbl_users');
    }
};
