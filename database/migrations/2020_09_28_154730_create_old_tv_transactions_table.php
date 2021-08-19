<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOldTvTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('old_tv_transactions', function (Blueprint $table) {
			$table->id();
			$table->string('trace_id');
			$table->string('transaction_id');
			$table->string('payment_reference')->nullable();
			$table->string('phone_number');
			$table->string('customer_name')->nullable();

			$table->integer('provider_id');
			$table->integer('package_id');
			$table->string('receiver');
			$table->string('amount');
			$table->string('email')->nullable();
			$table->enum('status', ['incomplete', 'pending', 'fulfilled', 'failed'])->default('incomplete');

			$table->string('request_time');
			$table->string('response_time')->nullable();

			$table->string('vend_request_time')->nullable();
			$table->string('vend_response_time')->nullable();

			$table->json('client_request');
			$table->json('client_response')->nullable();
			$table->json('client_vend_request')->nullable();
			$table->json('client_vend_response')->nullable();

			$table->json('api_request')->nullable();
			$table->json('api_response')->nullable();
			$table->json('api_vend_request')->nullable();
			$table->json('api_vend_response')->nullable();
			$table->string('date');
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
        Schema::dropIfExists('old_tv_transactions');
    }
}
