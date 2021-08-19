<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
// use phpDocumentor\Reflection\Types\This;

class AirtimeProvider extends Model
{
	protected $hidden = ["shortname", "service_type", "biller_id", "product_id", 'created_at', 'updated_at'];
	protected $fillable = [ "name", "shortname", "service_type", "biller_id", "product_id", "created_at", "updated_at"];

	public function packages()
	{
		return $this->hasMany(AirtimePackage::class, 'provider_id', 'id');
	}
}
