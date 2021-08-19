<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class OldDataTransaction extends Model
{
	protected $hidden = ['created_at', 'updated_at'];

	protected $fillable = [
		'old_id',
		'trace_id',
		'transaction_id',
		'payment_reference',
		'phone_number',
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


	// public function setOldIdAttribute()
	// {
	// 	return $this->attributes['id'];
	// }
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
		return $this->belongsTo(DataProvider::class, 'provider_id', 'id');
	}
	public function package()
	{
		return $this->belongsTo(DataPackage::class, 'package_id', 'id');
	}
}
