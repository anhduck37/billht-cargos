<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddActionAndDataToOrderHistorysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_historys', function (Blueprint $table) {
            $table->string('action')->nullable()->after('type_order');
            $table->longText('data')->nullable()->after('action');
            $table->string('tracking_code')->nullable()->after('data');
            $table->string('partner_name')->nullable()->after('tracking_code');
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
            $table->dropColumn('action');
            $table->dropColumn('data');
            $table->dropColumn('tracking_code');
            $table->dropColumn('partner_name');
        });
    }
}
