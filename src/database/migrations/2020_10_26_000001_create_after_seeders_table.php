<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAfterSeedersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('after_seeders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('seeder', 191)->nullable();
            $table->integer('batch')->unsigned()->nullable()->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('after_seeders');
    }
}
