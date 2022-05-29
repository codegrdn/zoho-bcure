<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOauthtokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('oauthtokens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('useridentifier', 100);
            $table->string('accesstoken', 100);
            $table->string('refreshtoken', 100);
            $table->bigInteger('expirytime');
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
        Schema::dropIfExists('oauthtokens');
    }
}
