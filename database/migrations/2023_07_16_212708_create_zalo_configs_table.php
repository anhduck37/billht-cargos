<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateZaloConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('zalo_configs', function (Blueprint $table) {
            $table->id();
            $table->string('app_id');
            $table->string('template_id');
            $table->string('secret_key');
            $table->string('access_token')->nullable();
            $table->string('refresh_token');
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
            $table->index(['app_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('zalo_configs');
    }
}
