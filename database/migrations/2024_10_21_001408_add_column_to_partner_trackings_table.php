<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToPartnerTrackingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('partner_trackings', function (Blueprint $table) {
            $table->string('money_totalfee')->nullable();
            $table->tinyInteger('order_payment')->nullable();
            $table->string('expected_delivery_date')->nullable();
            $table->longText('detail')->nullable();
            $table->bigInteger('voucher_value')->nullable();
            $table->string('money_collection_origin')->nullable();
            $table->string('employee_name')->nullable();
            $table->string('employee_phone')->nullable();
            $table->boolean('is_returning')->nullable();
            $table->string('pod')->nullable();
            $table->string('receiver_fullname')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('partner_trackings', function (Blueprint $table) {
            $table->dropColumn('money_totalfee');
            $table->dropColumn('order_payment');
            $table->dropColumn('expected_delivery_date');
            $table->dropColumn('detail');
            $table->dropColumn('voucher_value');
            $table->dropColumn('money_collection_origin');
            $table->dropColumn('employee_name');
            $table->dropColumn('employee_phone');
            $table->dropColumn('is_returning');
            $table->dropColumn('pod');
            $table->dropColumn('receiver_fullname');
        });
    }
}
