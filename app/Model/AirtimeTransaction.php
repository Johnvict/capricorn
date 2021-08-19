<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class AirtimeTransaction extends Model
{
	protected $hidden = ['created_at', 'updated_at'];

	protected $fillable = [
		'trace_id',
		'transaction_id',
		'phone_number',
		'provider',
		'provider_id',
		'package_id',
		'receiver',
		'amount',
		'email',
		'status',
		'payment_reference',

		'request_time',
		'response_time',
		'client_request',
		'client_response',
		'api_request',
		'api_response',
		'date'
	];

	public function getClientRequestAttribute()
	{
		return json_decode($this->attributes['client_request']);
	}

	public function getApiRequestAttribute()
	{
		return json_decode($this->attributes['api_request']);
	}
	
	public function getClientResponseAttribute()
	{
		return json_decode($this->attributes['client_response']);
	}
	public function getApiResponseAttribute()
	{
		return json_decode($this->attributes['api_response']);
	}

	public function provider()
	{
		return $this->belongsTo(AirtimeProvider::class, 'provider_id', 'id');
	}
	public function package()
	{
		return $this->belongsTo(AirtimePackage::class, 'package_id', 'id');
	}
}
