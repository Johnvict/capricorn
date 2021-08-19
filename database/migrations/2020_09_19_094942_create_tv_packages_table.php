<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTvPackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tv_packages', function (Blueprint $table) {
			$table->id();
			$table->string('name');
			$table->string('allowance')->nullable();
			$table->integer('amount')->nullable();
			$table->string('validity')->nullable();
			$table->string('datacode')->nullable();
			$table->integer('provider_id');
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
        Schema::dropIfExists('tv_packages');
    }
}
