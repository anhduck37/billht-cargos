<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSmartImportRowsTable extends Migration
{
    public function up()
    {
        Schema::create('smart_import_rows', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('smart_import_batch_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->integer('row_number');
            $table->string('status', 30)->default('error');
            $table->json('raw_data')->nullable();
            $table->json('editable_data')->nullable();
            $table->json('analysis')->nullable();
            $table->json('errors')->nullable();
            $table->json('warnings')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->index(['smart_import_batch_id', 'status']);
            $table->index('order_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('smart_import_rows');
    }
}
