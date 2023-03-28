<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_historys', function (Blueprint $table) {
            $table->dropColumn('request');
            $table->dropColumn('order_old');
            $table->dropColumn('order_new');
        });
        Schema::table('order_trackings', function (Blueprint $table) {
            $table->dropColumn('request');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_historys', function (Blueprint $table) {
            $table->longText('order_old')->nullable();
            $table->longText('order_new');
            $table->longText('request');
        });
        Schema::table('order_trackings', function (Blueprint $table) {
            $table->longText('request')->nullable();
        });
    }
}
