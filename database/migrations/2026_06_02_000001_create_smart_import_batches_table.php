<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSmartImportBatchesTable extends Migration
{
    public function up()
    {
        Schema::create('smart_import_batches', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('token', 80)->unique();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable();
            $table->string('status', 30)->default('preview');
            $table->integer('total_rows')->default(0);
            $table->integer('valid_rows')->default(0);
            $table->integer('error_rows')->default(0);
            $table->integer('imported_rows')->default(0);
            $table->json('summary')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('smart_import_batches');
    }
}
