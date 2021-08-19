<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
// use phpDocumentor\Reflection\Types\This;

class PowerProvider extends Model
{
	// protected $hidden = ["shortname", "service_type", "biller_id", "product_id", 'created_at', 'updated_at'];
	protected $fillable = [ "name", "shortname", "irecharge_name", "service_type", "biller_id", "product_id"];

	// ! We need to use product id here because we'll be using the product id sent from the API provider to validate existence of value
	// public function getIdAttribute() {
	// 	return $this->attributes['product_id'];
	// }

	public function packages()
	{
		return $this->hasMany(PowerPackage::class, 'provider_id', 'id');
	}
}
