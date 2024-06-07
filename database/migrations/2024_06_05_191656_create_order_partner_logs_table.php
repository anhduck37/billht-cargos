<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderPartnerLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_partner_logs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('order_id');
            $table->string('partner_code');
            $table->tinyInteger('status');
            $table->longText('payload');
            $table->longText('response');
            $table->bigInteger('user_id');
            $table->timestamps();
            $table->index(['order_id', 'partner_code', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_partner_logs');
    }
}
