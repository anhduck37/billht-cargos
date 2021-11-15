<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnDeliveryStatusOrderTrackingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_trackings', function (Blueprint $table) {
            $table->tinyInteger('delivery_status')->nullable();
            $table->integer('city_id')->nullable();
            $table->text('person_charge')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_trackings', function (Blueprint $table) {
            //
        });
    }
}
