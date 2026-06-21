<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderCodeAliasesTable extends Migration
{
    public function up()
    {
        Schema::create('order_code_aliases', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->string('old_code', 50);
            $table->string('new_code', 50);
            $table->string('reason', 100)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('old_code');
            $table->index('new_code');
            $table->unique(['order_id', 'old_code'], 'order_code_alias_order_old_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_code_aliases');
    }
}
