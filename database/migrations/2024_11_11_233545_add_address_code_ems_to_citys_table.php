<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAddressCodeEmsToCitysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('citys', function (Blueprint $table) {
            $table->integer('ems_code')->nullable();
        });
        Schema::table('districts', function (Blueprint $table) {
            $table->integer('ems_code')->nullable();
        });
        Schema::table('wards', function (Blueprint $table) {
            $table->integer('ems_code')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('citys', function (Blueprint $table) {
            $table->dropColumn('ems_code');
        });
        Schema::table('districts', function (Blueprint $table) {
            $table->dropColumn('ems_code');
        });
        Schema::table('wards', function (Blueprint $table) {
            $table->dropColumn('ems_code');
        });
    }
}
