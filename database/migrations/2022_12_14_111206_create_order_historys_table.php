<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderHistorysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_historys', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('order_id')->index();
            $table->bigInteger('user_id')->index();
            $table->longText('order_old')->nullable();
            $table->longText('order_new');
            $table->longText('request');
            $table->tinyInteger('type_order')->default(1);
            $table->tinyInteger('user_level');
            $table->tinyInteger('is_total_order')->default(0);
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
        Schema::dropIfExists('order_historys');
    }
}
