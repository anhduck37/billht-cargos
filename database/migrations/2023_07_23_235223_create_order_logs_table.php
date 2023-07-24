<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_logs', function (Blueprint $table) {
            $table->id();
            $table->string('order_code');
            $table->longText('request')->nullable();
            $table->longText('response');
            $table->tinyInteger('action')->default(0);
            $table->string('path');
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
            $table->index(['order_code', 'action', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_logs');
    }
}
