<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddYearYearFolderIdToGoogleDrivesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('google_drives', function (Blueprint $table) {
            $table->string('year_folder_id');
            $table->integer('year');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('google_drives', function (Blueprint $table) {
            $table->dropColumn('year_folder_id');
            $table->dropColumn('year');
        });
    }
}
