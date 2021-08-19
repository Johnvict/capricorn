<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class OldPowerTransaction  extends Model
{
	protected $hidden = ['created_at', 'updated_at'];

	protected $fillable = [
		'trace_id',
		'transaction_id',
		'payment_reference',
		'phone_number',
		'customer_name',
		'customer_address',
		'provider_id',
		'meter_number',
		'amount',
		'power_amount',
		'token',
		'meter_type',
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

	public function setClientRequestAttribute()
	{
		return json_encode($this->attributes['client_request']);
	}

	public function setApiRequestAttribute()
	{
		return json_encode($this->attributes['api_request']);
	}
	public function setApiVendRequestAttribute()
	{
		return json_encode($this->attributes['api_vend_request']);
	}
	public function setApiVendResponseAttribute()
	{
		return json_encode($this->attributes['api_vend_response']);
	}
	public function setClientResponseAttribute()
	{
		return json_encode($this->attributes['client_response']);
	}
	public function setApiResponseAttribute()
	{
		return json_encode($this->attributes['api_response']);
	}
	public function setClientVendRequestAttribute()
	{
		return json_encode($this->attributes['client_vend_request']);
	}
	public function setClientVendResponseAttribute()
	{
		return json_encode($this->attributes['client_vend_response']);
	}

	public function provider()
	{
		return $this->belongsTo(PowerProvider::class, 'provider_id', 'id');
	}

}
