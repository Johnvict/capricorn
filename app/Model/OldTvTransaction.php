<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class OldTvTransaction extends Model
{
	protected $hidden = ['created_at', 'updated_at'];

	protected $fillable = [
		'trace_id',
		'transaction_id',
		'payment_reference',
		'phone_number',
		'customer_name',
		'provider_id',
		'package_id',
		'receiver',
		'amount',
		'email',
		'status',
		'request_time',
		'response_time',
		'vend_request_time',
		'vend_response_time',
		'client_request',
		'client_response',
		'client_vend_request',
		'client_vend_response',
		'api_request',
		'api_response',
		'api_vend_request',
		'api_vend_response'
	];

	public function getClientRequestAttribute()
	{
		return json_decode($this->attributes['client_request']);
	}

	public function getApiRequestAttribute()
	{
		return json_decode($this->attributes['api_request']);
	}
	public function getApiVendRequestAttribute()
	{
		return json_decode($this->attributes['api_vend_request']);
	}
	public function getApiVendResponseAttribute()
	{
		return json_decode($this->attributes['api_vend_response']);
	}
	public function getClientResponseAttribute()
	{
		return json_decode($this->attributes['client_response']);
	}
	public function getApiResponseAttribute()
	{
		return json_decode($this->attributes['api_response']);
	}
	public function getClientVendRequestAttribute()
	{
		return json_decode($this->attributes['client_vend_request']);
	}
	public function getClientVendResponseAttribute()
	{
		return json_decode($this->attributes['client_vend_response']);
	}

	public function provider()
	{
		return $this->belongsTo(TvProvider::class, 'provider_id', 'id');
	}
	public function package()
	{
		return $this->belongsTo(TvPackage::class, 'package_id', 'id');
	}
}
