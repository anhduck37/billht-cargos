<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewAddressPartnerMappingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('new_address_partner_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('new_province_id');
            $table->unsignedBigInteger('new_ward_id');
            $table->string('partner_code'); // 'VTP' or 'EMS'
            $table->string('partner_province_code')->nullable();
            $table->string('partner_district_code')->nullable();
            $table->string('partner_ward_code')->nullable();
            $table->string('mapping_status')->default('mapped'); // mapped, missing, manual_review
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('new_ward_id');
            $table->index('partner_code');
            $table->unique(['new_ward_id', 'partner_code']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('new_address_partner_mappings');
    }
}
