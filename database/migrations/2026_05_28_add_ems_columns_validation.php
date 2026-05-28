<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;


class EmsColumnsValidation extends Migration

{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Ensure push_error column exists
            if (!Schema::hasColumn('orders', 'push_error')) {
                $table->text('push_error')->nullable()->after('partner_code');
            }
            
            // Ensure order_partner_code column exists
            if (!Schema::hasColumn('orders', 'order_partner_code')) {
                $table->string('order_partner_code')->nullable()->after('partner_code');
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
            //
        });
    }
}
