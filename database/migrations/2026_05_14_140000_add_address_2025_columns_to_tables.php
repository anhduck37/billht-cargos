<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAddress2025ColumnsToTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'address_scheme')) {
                $table->string('address_scheme')->default('old');
            }
        });

        Schema::table('senders', function (Blueprint $table) {
            if (!Schema::hasColumn('senders', 'address_scheme')) {
                $table->string('address_scheme')->default('old');
            }
            if (!Schema::hasColumn('senders', 'new_province_id')) {
                $table->bigInteger('new_province_id')->unsigned()->nullable();
            }
            if (!Schema::hasColumn('senders', 'new_ward_id')) {
                $table->bigInteger('new_ward_id')->unsigned()->nullable();
            }
        });

        Schema::table('receivers', function (Blueprint $table) {
            if (!Schema::hasColumn('receivers', 'address_scheme')) {
                $table->string('address_scheme')->default('old');
            }
            if (!Schema::hasColumn('receivers', 'new_province_id')) {
                $table->bigInteger('new_province_id')->unsigned()->nullable();
            }
            if (!Schema::hasColumn('receivers', 'new_ward_id')) {
                $table->bigInteger('new_ward_id')->unsigned()->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('address_scheme');
        });

        Schema::table('senders', function (Blueprint $table) {
            $table->dropColumn(['address_scheme', 'new_province_id', 'new_ward_id']);
        });

        Schema::table('receivers', function (Blueprint $table) {
            $table->dropColumn(['address_scheme', 'new_province_id', 'new_ward_id']);
        });
    }
}
