<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewWardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('new_wards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('new_province_id')->index();
            $table->string('name');
            $table->string('official_code')->nullable()->index();
            $table->string('normalized_name')->index();
            $table->boolean('is_active')->default(1)->index();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('new_wards');
    }
}
