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
        Schema::create('tbl_users_login', function (Blueprint $table) {
            $table->id("id_user_login");
            $table->unsignedBigInteger("id_user");
            $table->text("login_token");
            $table->datetime("expired");
            $table->text("device_info");
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
        Schema::dropIfExists('tbl_users_login');
    }
};
