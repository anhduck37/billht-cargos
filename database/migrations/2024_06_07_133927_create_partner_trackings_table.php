<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartnerTrackingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('partner_trackings', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('order_id');
            $table->string('order_partner_code');
            $table->string('order_reference')->nullable();
            $table->string('order_statusdate')->nullable();
            $table->tinyInteger('order_status')->nullable();
            $table->string('status_name')->nullable();
            $table->string('location_currently')->nullable();
            $table->longText('note')->nullable();
            $table->bigInteger('money_conllection')->nullable();
            $table->bigInteger('money_feecod')->nullable();
            $table->bigInteger('money_total')->nullable();
            $table->string('expected_delivery')->nullable();
            $table->bigInteger('product_weight')->nullable();
            $table->string('order_service')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'order_partner_code', 'order_status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('partner_trackings');
    }
}
